<!-- 
    Caleb Luh 
    11/20/2025
    Pickup Basketball Stat Tracker App
    CIS 4004 - LAMP-Stack Web Application Project 3
-->

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pickup Basketball Stat Lab</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Pickup Basketball Stat Lab</h1>
        <nav>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php">Dashboard</a>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
                    <a href="players_admin.php">Manage Players</a>
                    <a href="game_entry.php">Enter Game</a>
                <?php endif; ?>
                <a href="player_view.php">View Stats</a>
                <a href="logout.php">Logout</a>
            <?php endif; ?>
        </nav>
    </header>
    <main>
