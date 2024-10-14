<?php
session_start();
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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_meeting'])) {
    $user_id = $_SESSION['user_id'];
    $preferred_date = $_POST['preferred_date'];
    $preferred_time = $_POST['preferred_time'];

    $stmt = $pdo->prepare('INSERT INTO nutritionist_requests (user_id, preferred_date, preferred_time) VALUES (?, ?, ?)');
    $stmt->execute([$user_id, $preferred_date, $preferred_time]);

    header('Location: user_dashboard.php'); // Redirect after requesting meeting
    exit;
}
?>
