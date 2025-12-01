<!-- 
    Caleb Luh 
    11/20/2025
    Pickup Basketball Stat Tracker App
    CIS 4004 - LAMP-Stack Web Application Project 3
-->

<?php

session_start(); 
// Make sure the user is logged in
if (!isset($_SESSION['user_role'])) { 
    header('Location: index.php'); // Kick them back to login if not    
    exit; 
} 
 
$role = $_SESSION['user_role']; 
$username = $_SESSION['username']; 
?> 
 
<!DOCTYPE html> 
<html lang="en"> 
<head> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">     
    <title>Dashboard</title> 
    <link rel="stylesheet" href="style.css"> 
</head> 
<body> 
    <div class="container"> 
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--clr-surface-a20); padding-bottom: 10px; margin-bottom: 20px;">
            <h1 style="border-bottom: none; padding-bottom: 0; margin-top: 0;">Welcome back, <?php echo htmlspecialchars($username); ?>!</h1> 
            <a href="logout.php" class="btn btn-danger">Logout</a> 
        </div>
        
        <?php if ($role === 'Admin'): ?> 
            <h2>Admin Dashboard</h2> 
            <div class="admin-links"> 
                <a href="players_admin.php" class="btn">Manage Players</a> 
                <a href="games_admin.php" class="btn">Manage Games</a>
                <a href="game_entry.php" class="btn">Record New Game & Stats</a> 
            </div> 
             
        <div class="standard-links" style="margin-top: 30px;"> 
            <a href="stats_view.php" class="btn btn-success">View Player Stats & Averages</a> 
        </div> 
 
        <?php elseif ($role === 'Standard'): ?> 
            <h2>Dashboard</h2> 
            <div class="standard-links"> 
                <a href="stats_view.php" class="btn btn-success">View Player Stats & Averages</a> 
            </div> 
        <?php endif; ?> 
    </div> 
</body> 
</html> 
