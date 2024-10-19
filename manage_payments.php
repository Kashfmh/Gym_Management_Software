<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

// Database connection
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

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['payment_id']) && isset($_POST['action'])) {
        $payment_id = $_POST['payment_id'];
        $action = $_POST['action'];

        if ($action === 'mark_completed') {
            $stmt = $pdo->prepare("UPDATE payments SET status = 'completed' WHERE id = :id");
            $stmt->execute(['id' => $payment_id]);

            if ($stmt->rowCount() > 0) {
                $_SESSION['status'] = 'Payment marked as completed.';
            } else {
                $_SESSION['status'] = 'Payment ID not found.';
            }
        } elseif ($action === 'mark_failed') {
            $stmt = $pdo->prepare("UPDATE payments SET status = 'failed' WHERE id = :id");
            $stmt->execute(['id' => $payment_id]);

            if ($stmt->rowCount() > 0) {
                $_SESSION['status'] = 'Payment marked as failed.';
            } else {
                $_SESSION['status'] = 'Payment ID not found.';
            }
        }

        header('Location: admin_dashboard.php'); // Redirect back to the dashboard
        exit;
    } else {
        $_SESSION['status'] = 'Invalid request.';
        header('Location: admin_dashboard.php');
        exit;
    }
}
