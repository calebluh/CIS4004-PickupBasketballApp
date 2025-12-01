<!-- 
    Caleb Luh 
    11/20/2025
    Pickup Basketball Stat Tracker App
    CIS 4004 - LAMP-Stack Web Application Project 3
-->

<?php
include 'includes/header.php';
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$sql = "SELECT p.name, 
        COUNT(gs.game_id) as games_played,
        SUM(gs.points) as total_points,
        AVG(gs.points) as ppg
        FROM Players p
        LEFT JOIN Game_Stats gs ON p.player_id = gs.player_id
        GROUP BY p.player_id";
$result = $conn->query($sql);
?>

<h2>Player Stats</h2>
<table>
    <tr>
        <th>Player</th>
        <th>Games Played</th>
        <th>Total Points</th>
        <th>PPG</th>
    </tr>
    <?php while($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?php echo htmlspecialchars($row['name']); ?></td>
        <td><?php echo $row['games_played']; ?></td>
        <td><?php echo $row['total_points'] ? $row['total_points'] : 0; ?></td>
        <td><?php echo number_format($row['ppg'], 1); ?></td>
    </tr>
    <?php endwhile; ?>
</table>

<?php include 'includes/footer.php'; ?>
