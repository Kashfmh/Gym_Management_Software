<?php
session_start();
require 'database_connection.php';
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['payment_id'])) {
        // Update existing payment
        $paymentId = filter_var($_POST['payment_id'], FILTER_VALIDATE_INT);
        $action = $_POST['action'];

        if ($paymentId === false) {
            $_SESSION['status'] = 'Invalid payment ID.';
            header('Location: admin_dashboard.php');
            exit;
        }

        if ($action === 'mark_completed') {
            $updatePayment = $pdo->prepare("UPDATE payments SET status = 'Completed' WHERE id = ?");
            $updatePayment->execute([$paymentId]);
            $_SESSION['status'] = 'Payment marked as completed.';
        } elseif ($action === 'mark_failed') {
            $updatePayment = $pdo->prepare("UPDATE payments SET status = 'Failed' WHERE id = ?");
            $updatePayment->execute([$paymentId]);
            $_SESSION['status'] = 'Payment marked as failed.';
        }
    } else {
        // Insert new payment
        $userId = $_POST['user_id']; 
        $amount = $_POST['amount'];
        $paymentMethod = $_POST['payment_method'];

        $insertPayment = $pdo->prepare("INSERT INTO payments (user_id, amount, payment_method, payment_date, status) VALUES (?, ?, ?, NOW(), 'Pending')");
        $insertPayment->execute([$userId, $amount, $paymentMethod]);
        $_SESSION['status'] = 'New payment request created.';
    }

    header('Location: admin_dashboard.php');
    exit;
}
?>
