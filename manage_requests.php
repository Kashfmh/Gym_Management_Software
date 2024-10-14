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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $request_id = $_POST['request_id'];

    if (isset($_POST['approve'])) {
        $stmt = $pdo->prepare('UPDATE nutritionist_requests SET status = "Approved" WHERE id = ?');
        $stmt->execute([$request_id]);
    }

    if (isset($_POST['reject'])) {
        $stmt = $pdo->prepare('UPDATE nutritionist_requests SET status = "Rejected" WHERE id = ?');
        $stmt->execute([$request_id]);
    }

    header('Location: admin_dashboard.php'); // Redirect after updating request status
    exit;
}
?>
