<!-- 
    Caleb Luh 
    11/20/2025
    Pickup Basketball Stat Tracker App
    CIS 4004 - LAMP-Stack Web Application Project 3
-->

<?php 

session_start(); 
include 'db_connect.php'; 
 
// Make sure the user is logged in (Admin or Standard) 
if (!isset($_SESSION['user_role'])) {     
    header('Location: index.php');     
    exit; 
} 
 
$message = ''; 
$is_error = false; 
$player_stats_summary = []; 

// Grab all the unique years from the games table for the filter dropdown
$seasons = [];
try {
    $stmt_years = $pdo->query("SELECT DISTINCT YEAR(game_date) as season_year FROM Games ORDER BY season_year DESC");
    $seasons = $stmt_years->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
}

$selected_season = isset($_GET['season']) ? $_GET['season'] : 'all';

try { 
    // calculate averages and totals for every player     
    $sql = "
        SELECT 
            P.name, 
            P.player_id, 
            P.player_number,
            P.image_path,
            COUNT(GS.game_id) AS games_played, 
            SUM(GS.points) AS total_points, 
            AVG(GS.points) AS avg_points, 
            SUM(GS.rebounds) AS total_rebounds, 
            AVG(GS.rebounds) AS avg_rebounds, 
            SUM(GS.assists) AS total_assists, 
            AVG(GS.assists) AS avg_assists, 
            SUM(GS.steals) AS total_steals, 
            AVG(GS.steals) AS avg_steals,
            SUM(GS.blocks) AS total_blocks, 
            AVG(GS.blocks) AS avg_blocks,
            SUM(GS.turnovers) AS total_turnovers, 
            AVG(GS.turnovers) AS avg_turnovers,
            SUM(GS.fgm) AS total_fgm, 
            SUM(GS.fga) AS total_fga, 
            SUM(GS.3pm) AS total_3pm,
            SUM(GS.3pa) AS total_3pa,
            (SUM(GS.fgm) / NULLIF(SUM(GS.fga), 0)) * 100 AS fg_percentage 
        FROM 
            Players P 
        JOIN 
            Game_Stats GS ON P.player_id = GS.player_id 
        JOIN
            Games G ON GS.game_id = G.game_id
    ";

    if ($selected_season !== 'all') {
        $sql .= " WHERE YEAR(G.game_date) = :season_year ";
    }

    $sql .= "
        GROUP BY 
            P.player_id         
        ORDER BY 
            avg_points DESC 
    ";

    $stmt = $pdo->prepare($sql);
    if ($selected_season !== 'all') {
        $stmt->execute(['season_year' => $selected_season]);
    } else {
        $stmt->execute();
    }

    $player_stats_summary = $stmt->fetchAll(); 
} catch (PDOException $e) { 
    $message = "Database Error: Could not retrieve summary stats. " . $e->getMessage(); 
    $is_error = true; 
} 
 
// Helper to make percentages look nice 
function format_percent($value) {     
    return is_numeric($value) ? number_format($value, 1) . '%' : '0.0%'; 
} 
// Helper to format averages to one decimal place 
function format_avg($value) { 
    return is_numeric($value) ? number_format($value, 1) : '0.0'; 
} 
?> 
 
<!DOCTYPE html> 
<html lang="en"> 
<head> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Player Statistics View</title> 
    <link rel="stylesheet" href="style.css"> 
    <style>
        .player-img-thumb {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
            vertical-align: middle;
            margin-right: 10px;
        }
        .player-name-cell {
            display: flex;
            align-items: center;
        }
    </style>
</head> 
<body> 
    <div class="container"> 
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1 style="margin: 0; border: none;">Player Stats</h1>
            <form method="GET" style="margin: 0; padding: 0; background: none; border: none; box-shadow: none;">
                <label for="season" style="display: inline; margin-right: 10px; color: var(--clr-light-a0);">Season:</label>
                <select name="season" id="season" onchange="this.form.submit()" style="width: auto; display: inline-block; margin: 0; padding: 5px 10px;">
                    <option value="all" <?php echo $selected_season === 'all' ? 'selected' : ''; ?>>All Time</option>
                    <?php foreach ($seasons as $year): ?>
                        <option value="<?php echo htmlspecialchars($year); ?>" <?php echo $selected_season == $year ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($year); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <div class="nav-links"> 
            <a href="dashboard.php">← Dashboard</a> 
            <?php if ($_SESSION['user_role'] === 'Admin'): ?> 
                <a href="players_admin.php">Manage Players</a> 
                <a href="game_entry.php">Record Game</a> 
            <?php endif; ?> 
            <a href="logout.php">Logout</a> 
        </div> 
 
        <?php if ($message): ?> 
            <div class="message <?php echo $is_error ? 'error' : 'success'; ?>"><?php echo htmlspecialchars($message); ?></div> 
        <?php endif; ?> 
 
        <?php if (empty($player_stats_summary)): ?> 
            <div class="message error">No games or statistics have been recorded yet for this selection.</div>         
        <?php else: ?> 
            <table> 
                <thead> 
                    <tr> 
                        <th>Player</th> 
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
                    <?php foreach ($player_stats_summary as $stats): ?> 
                    <tr> 
                        <td>
                            <div class="player-name-cell">
                                <?php if (!empty($stats['image_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($stats['image_path']); ?>" alt="" class="player-img-thumb">
                                <?php endif; ?>
                                <div>
                                    <a href="player_profile.php?id=<?php echo $stats['player_id']; ?>" style="text-decoration: none; color: var(--clr-primary-a10); font-weight: bold;">
                                        <?php echo htmlspecialchars($stats['name']); ?>
                                    </a>
                                    <div style="font-size: 0.8em; color: var(--clr-surface-a50);">#<?php echo htmlspecialchars($stats['player_number'] ?? '-'); ?></div>
                                </div>
                            </div>
                        </td> 
                        <td><?php echo (int)$stats['games_played']; ?></td> 
                        <td><?php echo format_avg($stats['avg_points']); ?></td> 
                        <td><?php echo format_avg($stats['avg_rebounds']); ?></td> 
                        <td><?php echo format_avg($stats['avg_assists']); ?></td>                         
                        <td> 
                            <?php echo format_percent($stats['fg_percentage']); ?>  
                        </td> 
                        <td>
                            <?php 
                                $three_pct = ($stats['total_3pa'] > 0) ? ($stats['total_3pm'] / $stats['total_3pa']) * 100 : 0;
                                echo format_percent($three_pct); 
                            ?>
                        </td>
                        <td><?php echo format_avg($stats['avg_steals']); ?></td> 
                        <td><?php echo format_avg($stats['avg_blocks']); ?></td> 
                        <td><?php echo format_avg($stats['avg_turnovers']); ?></td> 
                    </tr> 
                    <?php endforeach; ?> 
                </tbody> 
            </table> 
        <?php endif; ?> 
    </div> 
</body> 
</html>
