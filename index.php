<!-- 
    Caleb Luh 
    11/20/2025
    Pickup Basketball Stat Tracker App
    CIS 4004 - LAMP-Stack Web Application Project 3
-->

<?php 

session_start(); 
include 'db_connect.php'; 
 
// If user is already logged in, send them straight to the dashboard
if (isset($_SESSION['user_role'])) {     
    header('Location: dashboard.php');     
    exit; 
} 
 
$message = ''; 
$is_error = false; 
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
    $username = trim($_POST['username']);     
    $password = $_POST['password'];  
    
    if (isset($_POST['action']) && $_POST['action'] === 'register') { 
        // Handle Registration
        // Check if username is taken
        $stmt = $pdo->prepare("SELECT user_id FROM Users WHERE username = ?"); 
        $stmt->execute([$username]); 
 
        if ($stmt->fetch()) { 
            $message = "Error: Username already exists."; 
            $is_error = true; 
        } else { 
            // Hash the password for security
            $hashed_password = password_hash($password, PASSWORD_DEFAULT); 
            $stmt = $pdo->prepare("INSERT INTO Users (username, password_hash, role) VALUES (?, ?, 'Standard')"); 
            $stmt->execute([$username, $hashed_password]); 
 
            $message = "Registration successful! You can now log in as a Standard User."; 
            $is_error = false; 
        } 
 
    } elseif (isset($_POST['action']) && $_POST['action'] === 'login') { 
        // Handle Login
        $stmt = $pdo->prepare("SELECT user_id, username, password_hash, role FROM Users WHERE username = ?"); 
        $stmt->execute([$username]);         
        $user = $stmt->fetch(); 
 
        if ($user && password_verify($password, $user['password_hash'])) { 
            // Login success: save user info to session
            $_SESSION['user_id'] = $user['user_id']; 
            $_SESSION['username'] = $user['username']; 
            $_SESSION['user_role'] = $user['role']; 
 
            header('Location: dashboard.php');             
            exit;         
        } else { 
            $message = "Error: Invalid username or password."; 
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
    <title>Login / Register</title> 
    <link rel="stylesheet" href="style.css"> 
</head> 
<body> 
    <div class="container" style="max-width: 450px;"> 
        <h1>Pickup Basketball Stats Tracker</h1> 
        <p>Log in or register to track your game stats.</p> 
 
        <?php if ($message): ?> 
            <div class="message <?php echo $is_error ? 'error' : 'success'; ?>"><?php echo htmlspecialchars($message); ?></div> 
        <?php endif; ?> 
 
        <!-- Login Form --> 
        <h2>Login</h2> 
        <form method="POST"> 
            <input type="hidden" name="action" value="login"> 
            <label for="login_username">Username:</label> 
            <input type="text" id="login_username" name="username" required>              
            <label for="login_password">Password:</label> 
            <input type="password" id="login_password" name="password" required>              
            <button type="submit">Log In</button> 
        </form> 
 
        <!-- Registration Form --> 
        <h2>Register</h2> 
        <form method="POST"> 
            <input type="hidden" name="action" value="register"> 
            <label for="reg_username">Username:</label> 
            <input type="text" id="reg_username" name="username" required>              
            <label for="reg_password">Password:</label> 
            <input type="password" id="reg_password" name="password" required>              
            <button type="submit" class="btn-success">Register</button> 
        </form> 
    </div> 
</body> 
</html> 
