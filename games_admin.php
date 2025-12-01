<!-- 
    Caleb Luh 
    11/20/2025
    Pickup Basketball Stat Tracker App
    CIS 4004 - LAMP-Stack Web Application Project 3
-->

<?php

session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    header('Location: index.php');
    exit;
}

$message = '';

// Handle Game Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_game'])) {
    $game_id = $_POST['game_id'];
    $stmt = $pdo->prepare("DELETE FROM Games WHERE game_id = ?");
    $stmt->execute([$game_id]);
    $message = "Game #$game_id deleted successfully.";
}

// Get all games to display in the table
$games = $pdo->query("
    SELECT G.*, COUNT(GS.player_id) as player_count 
    FROM Games G 
    LEFT JOIN Game_Stats GS ON G.game_id = GS.game_id 
    GROUP BY G.game_id 
    ORDER BY G.game_date DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Games</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Manage Games</h1>
        <div class="nav-links">
            <a href="dashboard.php">← Dashboard</a>
            <a href="game_entry.php">Record Game</a>
            <a href="players_admin.php">Manage Players</a>
            <a href="stats_view.php">View Stats</a>
            <a href="logout.php">Logout</a>
        </div>

        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Location</th>
                    <th>Result</th>
                    <th>Score</th>
                    <th>Players</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($games as $game): ?>
                <tr>
                    <td><?php echo date('m/d/Y', strtotime($game['game_date'])); ?></td>
                    <td><?php echo htmlspecialchars($game['location']); ?></td>
                    <td>
                        <?php 
                        if ($game['result'] === 'W') echo '<span style="color:var(--clr-success-a10)">Win</span>';
                        elseif ($game['result'] === 'L') echo '<span style="color:var(--clr-danger-a10)">Loss</span>';
                        else echo '-';
                        ?>
                    </td>
                    <td><?php echo $game['team_score'] . ' - ' . $game['opp_score']; ?></td>
                    <td><?php echo $game['player_count']; ?></td>
                    <td>
                        <a href="game_entry.php?edit_id=<?php echo $game['game_id']; ?>" class="btn">Edit</a>
                        <form method="POST" style="display:inline; background:none; padding:0; border:none;">
                            <input type="hidden" name="game_id" value="<?php echo $game['game_id']; ?>">
                            <button type="submit" name="delete_game" class="btn-danger" onclick="return confirm('Delete this game and all its stats?');">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>