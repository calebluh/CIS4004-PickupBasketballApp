<!-- 
    Caleb Luh 
    11/20/2025
    Pickup Basketball Stat Tracker App
    CIS 4004 - LAMP-Stack Web Application Project 3
-->

<?php
// API endpoint: api/compare_nba.php?id=PLAYER_ID
// Returns JSON comparing local averages to an NBA player via external API
header('Content-Type: application/json; charset=utf-8');
include_once __DIR__ . '/../includes/config.php';
include_once __DIR__ . '/../db_connect.php';

$player_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($player_id <= 0) {
    echo json_encode(['error' => 'missing_player_id']);
    exit;
}

// Get the player's stats to compare with NBA players
$stmt = $pdo->prepare("SELECT * FROM Players WHERE player_id = ?");
$stmt->execute([$player_id]);
$player = $stmt->fetch();
if (!$player) {
    echo json_encode(['error' => 'player_not_found']);
    exit;
}

$stmt = $pdo->prepare("SELECT 
        COUNT(game_id) AS games_played,
        SUM(points) AS total_points,
        AVG(points) AS avg_points,
        SUM(rebounds) AS total_rebounds,
        AVG(rebounds) AS avg_rebounds,
        SUM(assists) AS total_assists,
        AVG(assists) AS avg_assists,
        SUM(fgm) AS total_fgm,
        SUM(fga) AS total_fga,
        SUM(`3pm`) AS total_3pm,
        SUM(`3pa`) AS total_3pa
    FROM Game_Stats 
    WHERE player_id = ?");
$stmt->execute([$player_id]);
$stats = $stmt->fetch();

$fg_pct = ($stats['total_fga'] > 0) ? round(($stats['total_fgm'] / $stats['total_fga']) * 100, 1) : 0;
$three_pct = ($stats['total_3pa'] > 0) ? round(($stats['total_3pm'] / $stats['total_3pa']) * 100, 1) : 0;

$summary = [
    'name' => $player['name'],
    'games_played' => intval($stats['games_played']),
    'avg_points' => round(floatval($stats['avg_points']),1),
    'avg_rebounds' => round(floatval($stats['avg_rebounds']),1),
    'avg_assists' => round(floatval($stats['avg_assists']),1),
    'fg_pct' => $fg_pct,
    'three_pct' => $three_pct,
];

$apiKey = env('GEMINI_API_KEY', '');

// Prompt
$pickupStats = "PPG: {$summary['avg_points']}, RPG: {$summary['avg_rebounds']}, APG: {$summary['avg_assists']}, FG%: {$summary['fg_pct']}%, 3P%: {$summary['three_pct']}%";

$systemPrompt = "You are a roast-master basketball analyst. Your task is to identify an active NBA player whose stats and playstyle most closely resemble a user's self-reported pickup game stats. Use the provided Google Search tool to find accurate, up-to-date NBA statistics.

Output Requirements:
1. Keep it short (max 3-4 sentences).
2. Name the NBA player clearly.
3. Explain the match briefly but use humor/roasting. If their stats are bad (low points, low assists), make fun of them for being a 'cardio king' or 'bench warmer'. If they are good, be impressed but skeptical.
4. Do NOT use markdown formatting of any kind. No bold (**), no italics (*), no tables, no headers. Just plain text.";

$userQuery = "Analyze these pickup game stats: '{$pickupStats}'. Find the most similar active NBA player and compare them.";

$payload = [
    'contents' => [
        ['parts' => [['text' => $userQuery]]]
    ],
    'tools' => [
        ['google_search' => new \stdClass()]
    ],
    'systemInstruction' => [
        'parts' => [['text' => $systemPrompt]]
    ],
    'generationConfig' => [
        'temperature' => 0.7,
        'maxOutputTokens' => 8192
    ]
];

$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-preview-09-2025:generateContent?key=' . $apiKey;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL check for XAMPP/Localhost
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
$resp = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err || !$resp) {
    echo json_encode(['error'=>'api_request_failed: ' . $err,'details'=>$err,'player_summary'=>$summary]);
    exit;
}

$decoded = json_decode($resp, true);
$assistantText = null;
if (isset($decoded['candidates'][0]['content']['parts'][0]['text'])) {
    $assistantText = $decoded['candidates'][0]['content']['parts'][0]['text'];
}

$groundingSources = [];
$groundingMetadata = $decoded['candidates'][0]['groundingMetadata'] ?? null;
if ($groundingMetadata && !empty($groundingMetadata['groundingAttributions'])) {
    foreach ($groundingMetadata['groundingAttributions'] as $attribution) {
        if (!empty($attribution['web']['uri']) && !empty($attribution['web']['title'])) {
            $groundingSources[] = [
                'uri' => $attribution['web']['uri'],
                'title' => $attribution['web']['title'],
            ];
        }
    }
}

// Format output for the frontend
$responseText = $assistantText;
if (!empty($groundingSources)) {
    $responseText .= "\n\nSources:\n";
    foreach ($groundingSources as $src) {
        $responseText .= "- " . $src['title'] . " (" . $src['uri'] . ")\n";
    }
}

echo json_encode(['source'=>'ai','player_summary'=>$summary,'ai_text' => $responseText, 'raw_response'=>$decoded]);

?>
