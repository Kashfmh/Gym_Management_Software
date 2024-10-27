<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$admin_email = 'admin@gmail.com';
$admin_password = 'admin123'; 


$host = 'localhost'; 
$db = 'gym_management';
$user = 'root'; 
$pass = '';

try {
    
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    
    die("Could not connect to the database $db :" . $e->getMessage());
}
?>