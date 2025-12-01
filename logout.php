<!-- 
    Caleb Luh 
    11/20/2025
    Pickup Basketball Stat Tracker App
    CIS 4004 - LAMP-Stack Web Application Project 3
-->

<?php 

session_start(); 
 
// Wipe out all the session data 
session_unset(); 
session_destroy(); 
 
// Send them back to the login screen 
header('Location: index.php'); 
exit; 
?> 
