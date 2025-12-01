<!-- 
    Caleb Luh 
    11/20/2025
    Pickup Basketball Stat Tracker App
    CIS 4004 - LAMP-Stack Web Application Project 3
-->

<?php

session_start(); 
include 'db_connect.php';  

// Make sure the user is logged in 
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') { 
    header('Location: index.php');     
    exit; 
} 
 
$message = ''; 
$is_error = false; 
$edit_game = null;
$edit_stats = [];

// Check if we are editing an existing game
if (isset($_GET['edit_id'])) {
    $game_id = $_GET['edit_id'];
    $stmt = $pdo->prepare("SELECT * FROM Games WHERE game_id = ?");
    $stmt->execute([$game_id]);
    $edit_game = $stmt->fetch();

    if ($edit_game) {
        // Use LEFT JOIN to include stats even if the player record was deleted
        $stmt = $pdo->prepare("SELECT GS.*, P.name FROM Game_Stats GS LEFT JOIN Players P ON GS.player_id = P.player_id WHERE GS.game_id = ?");
        $stmt->execute([$game_id]);
        $edit_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $message = "Game not found.";
        $is_error = true;
    }
}
 
// Get list of players for the dropdown
try { 
    $players = $pdo->query("SELECT player_id, name, team FROM Players ORDER BY name ASC")->fetchAll(); 
    // Group players by team for the dropdown
    $teams = [];
    foreach ($players as $p) {
        $teamName = $p['team'] ?: 'Free Agents';
        if (!isset($teams[$teamName])) {
            $teams[$teamName] = [];
        }
        $teams[$teamName][] = $p;
    }
    ksort($teams);
} catch (PDOException $e) { 
    $players = []; 
    $teams = [];
    $message = "Error fetching players: " . $e->getMessage(); 
    $is_error = true; 
} 
 
// Save the game when the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_game'])) { 
    $game_date = trim($_POST['game_date']); 
    $location = trim($_POST['location']); 
    $format = trim($_POST['format']); 
    $team_score = (int)$_POST['team_score'];
    $opp_score = (int)$_POST['opp_score'];
    $result = $_POST['result'];
    $stats = $_POST['stats'] ?? []; // Array of player stats, default to empty if not set
    
    // Determine if this is an update
    $is_update = !empty($_POST['game_id']);
    
    // Fallback: If game_id is missing from POST but present in GET (URL), treat as update
    if (!$is_update && !empty($_GET['edit_id'])) {
        $is_update = true;
        $_POST['game_id'] = $_GET['edit_id'];
    }
 
    if (empty($game_date) || empty($location)) { 
        $message = "Date and Location are required."; 
        $is_error = true; 
    } else {         
        try { 
            $pdo->beginTransaction(); 
 
            if ($is_update) {
                // UPDATE existing game
                $game_id = $_POST['game_id'];
                $stmt = $pdo->prepare("UPDATE Games SET game_date = ?, location = ?, format = ?, team_score = ?, opp_score = ?, result = ? WHERE game_id = ?");
                $stmt->execute([$game_date, $location, $format, $team_score, $opp_score, $result, $game_id]);
                
                $stmt = $pdo->prepare("DELETE FROM Game_Stats WHERE game_id = ?");
                $stmt->execute([$game_id]);
            } else {
                // INSERT new game
                $stmt = $pdo->prepare("INSERT INTO Games (game_date, location, format, team_score, opp_score, result) VALUES (?, ?, ?, ?, ?, ?)"); 
                $stmt->execute([$game_date, $location, $format, $team_score, $opp_score, $result]); 
                $game_id = $pdo->lastInsertId(); 
            }
 
            // Insert stats for each player
            $stat_stmt = $pdo->prepare(" 
                INSERT INTO Game_Stats (game_id, player_id, points, rebounds, assists, steals, blocks, turnovers, fgm, fga, 3pm, 3pa, minutes)                 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
            "); 
             
            $players_recorded = 0; 
            if (is_array($stats)) {
                foreach ($stats as $player_id => $player_stats) { 
                    // Only insert if the player has any stats recorded
                    if (isset($player_stats['points']) && $player_stats['points'] !== '') { 
                        $stat_stmt->execute([ 
                            $game_id, 
                            $player_id,  
                            $player_stats['points'] ?? 0, 
                            $player_stats['rebounds'] ?? 0, 
                            $player_stats['assists'] ?? 0, 
                            $player_stats['steals'] ?? 0, 
                            $player_stats['blocks'] ?? 0, 
                            $player_stats['turnovers'] ?? 0, 
                            $player_stats['fgm'] ?? 0, 
                            $player_stats['fga'] ?? 0,
                            $player_stats['3pm'] ?? 0,
                            $player_stats['3pa'] ?? 0,
                            $player_stats['minutes'] ?? 0
                        ]); 
                        $players_recorded++; 
                    } 
                } 
            }
             
            // Check if any player stats were recorded             
            if ($players_recorded === 0) { 
                // Rollback if no stats were added (prevents empty games)
                throw new Exception("No player stats were recorded. Please ensure players are added to the game."); 
            } 
 
            $pdo->commit(); 
            $message = $is_update ? "Game updated successfully!" : "Game recorded successfully! Game ID: {$game_id} with {$players_recorded} players."; 
            
            // Refresh data if update
            if ($is_update) {
                $stmt = $pdo->prepare("SELECT * FROM Games WHERE game_id = ?");
                $stmt->execute([$game_id]);
                $edit_game = $stmt->fetch();
                
                $stmt = $pdo->prepare("SELECT GS.*, P.name FROM Game_Stats GS JOIN Players P ON GS.player_id = P.player_id WHERE GS.game_id = ?");
                $stmt->execute([$game_id]);
                $edit_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                // Clear form on success insert
                $_POST = [];
            }
             
        } catch (Exception $e) { 
            $pdo->rollBack(); 
            $message = "Error recording game: " . $e->getMessage(); 
            $is_error = true; 
        } 
    } 
} 
?> 
 
<!DOCTYPE html> 
<html lang="en"> 
<head> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Record Game Stats</title> 
    <link rel="stylesheet" href="style.css"> 
    <script> 
        // ensure FGM <= FGA before submission 
        function validateStats() {             
            const statRows = document.querySelectorAll('.player-stats-row');             
            for (let row of statRows) {                 
                const fgm = parseInt(row.querySelector('[name^="stats"][name$="[fgm]"]').value) || 0; 
                const fga = parseInt(row.querySelector('[name^="stats"][name$="[fga]"]').value) || 0; 
                 
                if (fgm > fga) { 
                    alert('Error: Field Goals Made (FGM) cannot be greater than Field Goals Attempted (FGA) for a player.');                     
                    return false; 
                }             
            } 
            return true; 
        } 

        function addPlayerToTable(playerData = null) {
            let playerId, playerName;

            if (playerData) {
                playerId = playerData.player_id;
                playerName = playerData.name;
            } else {
                const select = document.getElementById('playerSelect');
                playerId = select.value;
                if (!playerId) return;
                playerName = select.options[select.selectedIndex].text;
            }

            // Check if player already exists
            if (document.getElementById('row-' + playerId)) {
                if (!playerData) alert('Player already added!');
                return;
            }

            const tbody = document.getElementById('statsTableBody');
            const tr = document.createElement('tr');
            tr.id = 'row-' + playerId;
            tr.className = 'player-stats-row';
            
            const stats = playerData || {};
            
            tr.innerHTML = `
                <td><strong>${playerName}</strong></td>
                <td><input type="number" name="stats[${playerId}][minutes]" min="0" value="${stats.minutes || 0}" style="width: 60px;"></td>
                <td><input type="number" name="stats[${playerId}][points]" min="0" value="${stats.points || 0}" style="width: 60px;"></td>
                <td><input type="number" name="stats[${playerId}][rebounds]" min="0" value="${stats.rebounds || 0}" style="width: 60px;"></td>
                <td><input type="number" name="stats[${playerId}][assists]" min="0" value="${stats.assists || 0}" style="width: 60px;"></td>
                <td><input type="number" name="stats[${playerId}][steals]" min="0" value="${stats.steals || 0}" style="width: 60px;"></td>
                <td><input type="number" name="stats[${playerId}][blocks]" min="0" value="${stats.blocks || 0}" style="width: 60px;"></td>
                <td><input type="number" name="stats[${playerId}][turnovers]" min="0" value="${stats.turnovers || 0}" style="width: 60px;"></td>
                <td><input type="number" name="stats[${playerId}][fgm]" min="0" value="${stats.fgm || 0}" style="width: 60px;"></td>
                <td><input type="number" name="stats[${playerId}][fga]" min="0" value="${stats.fga || 0}" style="width: 60px;"></td>
                <td><input type="number" name="stats[${playerId}][3pm]" min="0" value="${stats['3pm'] || 0}" style="width: 60px;"></td>
                <td><input type="number" name="stats[${playerId}][3pa]" min="0" value="${stats['3pa'] || 0}" style="width: 60px;"></td>
                <td><button type="button" class="btn-danger" onclick="this.closest('tr').remove()" style="padding: 5px 10px;">X</button></td>
            `;
            tbody.appendChild(tr);
        }

        <?php if (!empty($edit_stats)): ?>
        window.addEventListener('DOMContentLoaded', () => {
            try {
                const existingStats = <?php echo json_encode($edit_stats); ?>;
                if (Array.isArray(existingStats)) {
                    existingStats.forEach(stat => {
                        // Handle case where player might have been deleted (name is null)
                        if (!stat.name) stat.name = 'Unknown Player (ID: ' + stat.player_id + ')';
                        addPlayerToTable(stat);
                    });
                }
            } catch (e) {
                console.error("Error loading existing stats:", e);
                alert("Error loading existing stats. Please check console.");
            }
        });
        <?php endif; ?>
    </script> 
</head> 
<body> 
    <div class="container"> 
        <h1><?php echo isset($edit_game) ? 'Edit Game Stats' : 'Record New Game Stats'; ?></h1> 
        <div class="nav-links"> 
            <a href="dashboard.php">← Dashboard</a> 
            <a href="players_admin.php">Manage Players</a> 
            <a href="games_admin.php">Manage Games</a>
            <a href="stats_view.php">View Stats</a>             
            <a href="logout.php">Logout</a> 
        </div> 
 
        <?php if ($message): ?> 
            <div class="message <?php echo $is_error ? 'error' : 'success'; ?>"><?php echo htmlspecialchars($message); ?></div> 
        <?php endif; ?> 
         
        <form method="POST" action="game_entry.php<?php echo !empty($edit_game) ? '?edit_id=' . $edit_game['game_id'] : ''; ?>" onsubmit="return validateStats();">             
            <input type="hidden" name="record_game" value="1"> 
            <?php if (!empty($edit_game)): ?>
                <input type="hidden" name="game_id" value="<?php echo $edit_game['game_id']; ?>">
            <?php endif; ?>
 
            <!-- Game Details --> 
            <h2>1. Game Details</h2> 
            <div style="display: flex; gap: 20px; flex-wrap: wrap;"> 
                <div style="flex: 1; min-width: 200px;"> 
                    <label for="game_date">Date:</label> 
                    <input type="date" id="game_date" name="game_date" value="<?php echo isset($edit_game) ? $edit_game['game_date'] : date('Y-m-d'); ?>" required> 
                </div> 
                <div style="flex: 1; min-width: 200px;"> 
                    <label for="location">Location:</label> 
                    <input type="text" id="location" name="location" required placeholder="Park Name or Gym" value="<?php echo isset($edit_game) ? htmlspecialchars($edit_game['location']) : ''; ?>"> 
                </div> 
                <div style="flex: 1; min-width: 200px;">
                    <label for="format">Format:</label> 
                    <select id="format" name="format" required> 
                        <?php 
                        $formats = ['5v5 Full-Court', '4v4 Full-Court', '4v4 Half-Court', '3v3 Half-Court', '2v2 Half-Court'];
                        foreach ($formats as $f) {
                            $selected = (isset($edit_game) && $edit_game['format'] === $f) ? 'selected' : '';
                            echo "<option value=\"$f\" $selected>$f</option>";
                        }
                        ?>
                    </select> 
                </div>
            </div> 

            <div style="display: flex; gap: 20px; margin-top: 15px; background: var(--clr-surface-a20); padding: 15px; border-radius: 8px;">
                <div style="flex: 1;">
                    <label for="result">Result:</label>
                    <select id="result" name="result">
                        <option value="W" <?php echo (isset($edit_game) && $edit_game['result'] === 'W') ? 'selected' : ''; ?>>Win</option>
                        <option value="L" <?php echo (isset($edit_game) && $edit_game['result'] === 'L') ? 'selected' : ''; ?>>Loss</option>
                    </select>
                </div>
                <div style="flex: 1;">
                    <label for="team_score">Team Score:</label>
                    <input type="number" id="team_score" name="team_score" value="<?php echo isset($edit_game) ? $edit_game['team_score'] : '0'; ?>">
                </div>
                <div style="flex: 1;">
                    <label for="opp_score">Opponent Score:</label>
                    <input type="number" id="opp_score" name="opp_score" value="<?php echo isset($edit_game) ? $edit_game['opp_score'] : '0'; ?>">
                </div>
            </div>
 
            <!-- Player Stats Table --> 
            <h2>2. Player Statistics</h2> 
            
            <div style="margin-bottom: 20px; display: flex; gap: 10px; align-items: flex-end;">
                <div style="flex-grow: 1;">
                    <label for="playerSelect">Add Player to Game:</label>
                    <select id="playerSelect">
                        <option value="">-- Select Player --</option>
                        <?php foreach ($teams as $teamName => $teamPlayers): ?>
                            <optgroup label="<?php echo htmlspecialchars($teamName); ?>">
                                <?php foreach ($teamPlayers as $p): ?>
                                    <option value="<?php echo $p['player_id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="button" class="btn" onclick="addPlayerToTable()">Add Player</button>
            </div>

            <?php if (empty($players)): ?> 
                <div class="error">No players found! Please add players via the Manage Players page first.</div> 
            <?php else: ?> 
                <table> 
                    <thead> 
                        <tr> 
                            <th>Player</th> 
                            <th>MIN</th>
                            <th>PTS</th> 
                            <th>REB</th> 
                            <th>AST</th> 
                            <th>STL</th> 
                            <th>BLK</th> 
                            <th>TO</th> 
                            <th>FGM</th> 
                            <th>FGA</th>
                            <th>3PM</th>
                            <th>3PA</th>
                            <th></th>
                        </tr> 
                    </thead> 
                        <tbody id="statsTableBody"> 
                    </tbody> 
                </table> 
                <button type="submit" class="btn-success" style="margin-top: 20px;">Record Game and All Stats</button> 
            <?php endif; ?> 
        </form> 
    </div> 
</body> 
</html> 
