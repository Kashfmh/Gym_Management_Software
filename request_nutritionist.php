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
    die("Could not connect to the database: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_meeting'])) {
    $user_id = $_POST['user_id']; // Get user_id from form
    $preferred_date = $_POST['preferred_date'];
    $preferred_time = $_POST['preferred_time'];
    $status = 'Pending';

    try {
        $stmt = $pdo->prepare("INSERT INTO nutritionist_requests (user_id, preferred_date, preferred_time, status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $preferred_date, $preferred_time, $status]);

        // Redirect after successful insert
        header('Location: admin_dashboard.php');
        exit;
    } catch (PDOException $e) {
        echo "Error saving request: " . $e->getMessage();
    }
}
