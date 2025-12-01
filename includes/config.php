<!-- 
    Caleb Luh 
    11/20/2025
    Pickup Basketball Stat Tracker App
    CIS 4004 - LAMP-Stack Web Application Project 3
-->

<?php
// Read the env file
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        // Break the line apart at the equals sign
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) continue;
        $key = trim($parts[0]);
        $val = trim($parts[1]);
        // Get rid of any quotes around the values
        $val = preg_replace('/^\"(.*)\"$/', '$1', $val);
        $val = preg_replace("/^'(.*)'$/", '$1', $val);
        // Save it to the environment so we can use it later
        if (!getenv($key)) putenv("$key=$val");
        if (!defined($key)) define($key, $val);
    }
} else {
    // If there's no .env file, we'll just use the defaults
}

// Helper function to get env variables without crashing if they're missing
function env($key, $default = null) {
    $v = getenv($key);
    return ($v === false) ? $default : $v;
}

?>
