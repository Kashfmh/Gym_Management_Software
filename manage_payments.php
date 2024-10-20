<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_id']) && isset($_POST['action'])) {
    $payment_id = filter_var($_POST['payment_id'], FILTER_VALIDATE_INT);
    $action = $_POST['action'];

    if ($payment_id === false) {
        $_SESSION['status'] = 'Invalid payment ID.';
        header('Location: admin_dashboard.php'); // Redirect back to the dashboard
        exit;
    }

    if ($action === 'mark_completed') {
        $updatePayment = $pdo->prepare("UPDATE payments SET status = 'Completed' WHERE id = ?");
        $updatePayment->execute([$payment_id]);
        $_SESSION['status'] = 'Payment marked as completed.';
    } elseif ($action === 'mark_failed') {
        $updatePayment = $pdo->prepare("UPDATE payments SET status = 'Failed' WHERE id = ?");
        $updatePayment->execute([$payment_id]);
        $_SESSION['status'] = 'Payment marked as failed.';
    } else {
        $_SESSION['status'] = 'Invalid action.';
    }

    header('Location: admin_dashboard.php'); // Redirect back to the dashboard
    exit;
}
?>
