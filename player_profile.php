<!-- 
    Caleb Luh 
    11/20/2025
    Pickup Basketball Stat Tracker App
    CIS 4004 - LAMP-Stack Web Application Project 3
-->

<?php

session_start();
include 'db_connect.php';

if (!isset($_GET['id'])) {
    header('Location: stats_view.php');
    exit;
}

$player_id = $_GET['id'];

// Get the player's info from the database
$stmt = $pdo->prepare("SELECT * FROM Players WHERE player_id = ?");
$stmt->execute([$player_id]);
$player = $stmt->fetch();

if (!$player) {
    echo "Player not found.";
    exit;
}

// Get all the stats for this player
$stmt = $pdo->prepare("
    SELECT 
        COUNT(game_id) AS games_played,
        SUM(points) AS total_points,
        AVG(points) AS avg_points,
        SUM(rebounds) AS total_rebounds,
        AVG(rebounds) AS avg_rebounds,
        SUM(assists) AS total_assists,
        AVG(assists) AS avg_assists,
        SUM(fgm) AS total_fgm,
        SUM(fga) AS total_fga,
        SUM(3pm) AS total_3pm,
        SUM(3pa) AS total_3pa
    FROM Game_Stats 
    WHERE player_id = ?
");
$stmt->execute([$player_id]);
$stats = $stmt->fetch();

// Pull up the last 5 games they played
$stmt = $pdo->prepare("
    SELECT 
        G.game_date,
        G.location,
        GS.points,
        GS.rebounds,
        GS.assists,
        GS.steals,
        GS.blocks,
        GS.turnovers,
        GS.fgm,
        GS.fga,
        GS.3pm AS three_pm,
        GS.3pa AS three_pa
    FROM Game_Stats GS
    JOIN Games G ON GS.game_id = G.game_id
    WHERE GS.player_id = ?
    ORDER BY G.game_date DESC
    LIMIT 5
");
$stmt->execute([$player_id]);
$recent_games = $stmt->fetchAll();

// Calculate age
$age = 'N/A';
if (!empty($player['birthdate'])) {
    $dob = new DateTime($player['birthdate']);
    $now = new DateTime();
    $age = $now->diff($dob)->y;
}

// Calculate shooting percentages
$fg_pct = ($stats['total_fga'] > 0) ? ($stats['total_fgm'] / $stats['total_fga']) * 100 : 0;
$three_pct = ($stats['total_3pa'] > 0) ? ($stats['total_3pm'] / $stats['total_3pa']) * 100 : 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($player['name']); ?> - Profile</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" style="max-width: 1100px;">
        <div class="nav-links" style="margin-bottom: 20px;">
            <a href="stats_view.php">← Back to Stats</a>
            <a href="dashboard.php">Dashboard</a>
        </div>

        <!-- ESPN Style Header -->
        <div class="espn-header-container">
            <div class="espn-header-left">
                <div class="espn-player-img-wrapper">
                    <?php if (!empty($player['image_path'])): ?>
                        <img src="<?php echo htmlspecialchars($player['image_path']); ?>" alt="<?php echo htmlspecialchars($player['name']); ?>" class="espn-player-img">
                    <?php else: ?>
                        <div class="espn-player-img-placeholder">#<?php echo htmlspecialchars($player['player_number'] ?? '?'); ?></div>
                    <?php endif; ?>
                </div>
                <div class="espn-player-info">
                    <h1 class="espn-player-name"><?php echo htmlspecialchars($player['name']); ?></h1>
                    <div class="espn-player-meta">
                        <?php echo htmlspecialchars($player['team'] ?? 'Free Agent'); ?> <span class="separator">•</span> #<?php echo htmlspecialchars($player['player_number'] ?? '--'); ?> <span class="separator">•</span> <?php echo htmlspecialchars($player['nickname'] ?? 'Hooper'); ?>
                    </div>
                    <div class="espn-player-bio-grid">
                        <div class="bio-cell">
                            <span class="bio-label">HT/WT</span>
                            <span class="bio-value"><?php echo htmlspecialchars($player['height'] ?? '--'); ?>, <?php echo htmlspecialchars($player['weight'] ?? '--'); ?> lbs</span>
                        </div>
                        <div class="bio-cell">
                            <span class="bio-label">Birthdate</span>
                            <span class="bio-value"><?php echo $player['birthdate'] ? date('m/d/Y', strtotime($player['birthdate'])) . " ($age)" : '--'; ?></span>
                        </div>
                        <div class="bio-cell">
                            <span class="bio-label">Status</span>
                            <span class="bio-value status-active">Active</span>
                        </div>
                    </div>
                    <div style="margin-top: 15px;">
                         <button id="compareBtn" class="btn-espn-action">Compare to NBA Player</button>
                    </div>
                </div>
            </div>
            <div class="espn-header-right">
                <div class="season-stats-box">
                    <div class="season-stats-header">2025-26 Regular Season Stats</div>
                    <div class="season-stats-row">
                        <div class="stat-item">
                            <div class="stat-label">PTS</div>
                            <div class="stat-number"><?php echo number_format($stats['avg_points'], 1); ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">REB</div>
                            <div class="stat-number"><?php echo number_format($stats['avg_rebounds'], 1); ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">AST</div>
                            <div class="stat-number"><?php echo number_format($stats['avg_assists'], 1); ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">FG%</div>
                            <div class="stat-number"><?php echo number_format($fg_pct, 1); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="compareResult" style="margin-top: 10px;"></div>

        <!-- Splits Section -->
        <div class="section-container">
            <h3 class="section-title">Splits</h3>
            <table class="espn-table">
                <thead>
                    <tr>
                        <th>Splits</th>
                        <th>GP</th>
                        <th>PTS</th>
                        <th>REB</th>
                        <th>AST</th>
                        <th>FG%</th>
                        <th>3P%</th>
                        <th>STL</th>
                        <th>BLK</th>
                        <th>TO</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Career</td>
                        <td><?php echo $stats['games_played']; ?></td>
                        <td><?php echo number_format($stats['avg_points'], 1); ?></td>
                        <td><?php echo number_format($stats['avg_rebounds'], 1); ?></td>
                        <td><?php echo number_format($stats['avg_assists'], 1); ?></td>
                        <td><?php echo number_format($fg_pct, 1); ?></td>
                        <td><?php echo number_format($three_pct, 1); ?></td>
                        <td><?php echo number_format($stats['avg_steals'] ?? 0, 1); ?></td>
                        <td><?php echo number_format($stats['avg_blocks'] ?? 0, 1); ?></td>
                        <td><?php echo number_format($stats['avg_turnovers'] ?? 0, 1); ?></td>
                    </tr>
                    <!-- Placeholder for L10 / Home / Away logic if implemented later -->
                    <tr>
                        <td>Last 5</td>
                        <td><?php echo count($recent_games); ?></td>
                        <td>--</td>
                        <td>--</td>
                        <td>--</td>
                        <td>--</td>
                        <td>--</td>
                        <td>--</td>
                        <td>--</td>
                        <td>--</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Stats Section -->
        <div class="section-container">
            <div class="section-header-row">
                <h3 class="section-title">Stats</h3>
                <a href="#" class="see-all-link">See All</a>
            </div>
            <table class="espn-table">
                <thead>
                    <tr>
                        <th>STATS</th>
                        <th>GP</th>
                        <th>MIN</th>
                        <th>FG%</th>
                        <th>3P%</th>
                        <th>FT%</th>
                        <th>REB</th>
                        <th>AST</th>
                        <th>BLK</th>
                        <th>STL</th>
                        <th>PF</th>
                        <th>TO</th>
                        <th>PTS</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Regular Season</td>
                        <td><?php echo $stats['games_played']; ?></td>
                        <td>--</td>
                        <td><?php echo number_format($fg_pct, 1); ?></td>
                        <td><?php echo number_format($three_pct, 1); ?></td>
                        <td>--</td>
                        <td><?php echo number_format($stats['avg_rebounds'], 1); ?></td>
                        <td><?php echo number_format($stats['avg_assists'], 1); ?></td>
                        <td><?php echo number_format($stats['avg_blocks'] ?? 0, 1); ?></td>
                        <td><?php echo number_format($stats['avg_steals'] ?? 0, 1); ?></td>
                        <td>--</td>
                        <td><?php echo number_format($stats['avg_turnovers'] ?? 0, 1); ?></td>
                        <td><?php echo number_format($stats['avg_points'], 1); ?></td>
                    </tr>
                    <tr>
                        <td>Career</td>
                        <td><?php echo $stats['games_played']; ?></td>
                        <td>--</td>
                        <td><?php echo number_format($fg_pct, 1); ?></td>
                        <td><?php echo number_format($three_pct, 1); ?></td>
                        <td>--</td>
                        <td><?php echo number_format($stats['avg_rebounds'], 1); ?></td>
                        <td><?php echo number_format($stats['avg_assists'], 1); ?></td>
                        <td><?php echo number_format($stats['avg_blocks'] ?? 0, 1); ?></td>
                        <td><?php echo number_format($stats['avg_steals'] ?? 0, 1); ?></td>
                        <td>--</td>
                        <td><?php echo number_format($stats['avg_turnovers'] ?? 0, 1); ?></td>
                        <td><?php echo number_format($stats['avg_points'], 1); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Recent Games Section -->
        <div class="section-container">
            <div class="section-header-row">
                <h3 class="section-title">Recent Games</h3>
                <a href="#" class="see-all-link">See All</a>
            </div>
            <table class="espn-table">
                <thead>
                    <tr>
                        <th>DATE</th>
                        <th>OPP</th>
                        <th>RESULT</th>
                        <th>MIN</th>
                        <th>FG%</th>
                        <th>3P%</th>
                        <th>FT%</th>
                        <th>REB</th>
                        <th>AST</th>
                        <th>BLK</th>
                        <th>STL</th>
                        <th>PF</th>
                        <th>TO</th>
                        <th>PTS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_games as $game): 
                        $g_fg_pct = ($game['fga'] > 0) ? ($game['fgm'] / $game['fga']) * 100 : 0;
                        $g_3p_pct = ($game['three_pa'] > 0) ? ($game['three_pm'] / $game['three_pa']) * 100 : 0;
                    ?>
                    <tr>
                        <td><?php echo date('D m/d', strtotime($game['game_date'])); ?></td>
                        <td>vs <?php echo htmlspecialchars($game['location']); ?></td>
                        <td><span class="result-win">W</span></td> <!-- Placeholder Result -->
                        <td>--</td>
                        <td><?php echo number_format($g_fg_pct, 1); ?></td>
                        <td><?php echo number_format($g_3p_pct, 1); ?></td>
                        <td>--</td>
                        <td><?php echo $game['rebounds']; ?></td>
                        <td><?php echo $game['assists']; ?></td>
                        <td><?php echo $game['blocks']; ?></td>
                        <td><?php echo $game['steals']; ?></td>
                        <td>--</td>
                        <td><?php echo $game['turnovers']; ?></td>
                        <td><?php echo $game['points']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>
    <script>
        document.getElementById('compareBtn').addEventListener('click', function(){
            var btn = this;
            btn.disabled = true;
            btn.textContent = 'Comparing...';
            var out = document.getElementById('compareResult');
            out.style.display = 'block';
            out.innerHTML = 'Loading comparison...';
            fetch('api/compare_nba.php?id=' + encodeURIComponent(<?php echo intval($player_id); ?>))
                .then(function(res){ return res.json(); })
                .then(function(data){
                    btn.disabled = false;
                    btn.textContent = 'Compare to NBA Player';
                    if (data.error) {
                        out.innerHTML = '<div class="message error"><strong>Error:</strong> ' + (data.error_description || data.error || JSON.stringify(data)) + '</div>';
                        return;
                    }
                    if (data.source === 'local_fallback') {
                        out.innerHTML = '<div class="message success"><strong>Closest NBA player (local):</strong> ' + data.closest_nba.name + '<br>' +
                            '<strong>NBA PPG/RPG/APG:</strong> ' + data.closest_nba.ppg + '/' + data.closest_nba.rpg + '/' + data.closest_nba.apg + '<br>' +
                            '<strong>FG% / 3P%:</strong> ' + data.closest_nba.fg_pct + '% / ' + data.closest_nba.three_pct + '%<br>' +
                            '<em>' + data.explain + '</em></div>';
                        return;
                    }
                    if (data.source === 'ai') {
                        if (data.ai) {
                            out.innerHTML = '<div class="message success"><pre style="white-space:pre-wrap; font-family:inherit;">' + JSON.stringify(data.ai, null, 2) + '</pre></div>';
                        } else if (data.ai_text) {
                            out.innerHTML = '<div class="message success"><pre style="white-space:pre-wrap; font-family:inherit;">' + data.ai_text + '</pre></div>';
                        } else {
                            out.innerHTML = '<div class="message success"><pre>' + JSON.stringify(data, null, 2) + '</pre></div>';
                        }
                        return;
                    }
                    out.innerHTML = '<div class="message success"><pre>' + JSON.stringify(data, null, 2) + '</pre></div>';
                })
                .catch(function(err){
                    btn.disabled = false;
                    btn.textContent = 'Compare to NBA Player';
                    out.innerHTML = '<div class="message error"><strong>Error calling compare API:</strong> ' + err + '</div>';
                });
        });
    </script>
</body>
</html>
