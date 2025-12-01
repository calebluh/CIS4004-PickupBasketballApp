<!-- 
    Caleb Luh 
    11/20/2025
    Pickup Basketball Stat Tracker App
    CIS 4004 - LAMP-Stack Web Application Project 3
-->

<?php

// Set up the database connection using values from config file
include_once __DIR__ . '/includes/config.php';

$host = env('DB_HOST', '127.0.0.1');
$db   = env('DB_NAME', 'pickuphoops');
$user = env('DB_USER', 'root');
$pass = env('DB_PASS', '');
$charset = env('DB_CHARSET', 'utf8mb4');

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     echo "<h1>Database Connection Error</h1>";
     echo "<p>Please ensure your MySQL service is running on your local machine.</p>";
     echo "<p>Details: " . htmlspecialchars($e->getMessage()) . "</p>";
     exit();
}
?>