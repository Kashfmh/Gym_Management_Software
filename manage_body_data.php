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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_body_data'])) {
    $user_id = $_SESSION['user_id'];
    $weight = $_POST['weight'];
    $exercise = $_POST['exercise'];
    $water_consumption = $_POST['water_consumption'];
    $date = $_POST['date'];

    $stmt = $pdo->prepare('INSERT INTO body_data (user_id, weight, exercise, water_consumption, date) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$user_id, $weight, $exercise, $water_consumption, $date]);

    header('Location: user_dashboard.php'); // Redirect after saving data
    exit;
}
?>
