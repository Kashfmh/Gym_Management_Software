<?php
session_start();
require 'database_connection.php';
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id']) && isset($_POST['action'])) {
    $request_id = filter_var($_POST['request_id'], FILTER_VALIDATE_INT);
    $action = $_POST['action'];

    if ($request_id === false) {
        echo "Invalid request ID.";
        exit;
    }

    if ($action === 'approve') {
        $updateRequest = $pdo->prepare("UPDATE nutritionist_requests SET status = 'Approved' WHERE id = ?");
        $updateRequest->execute([$request_id]);

        if ($updateRequest->rowCount() > 0) {
            $updatePayment = $pdo->prepare("UPDATE payments SET status = 'Approved' WHERE request_id = ?");
            $updatePayment->execute([$request_id]);
            $_SESSION['status'] = 'Request approved and payment updated.';
        } else {
            $_SESSION['status'] = 'Request ID not found.';
        }
    } elseif ($action === 'reject') {
        $updateRequest = $pdo->prepare("UPDATE nutritionist_requests SET status = 'Rejected' WHERE id = ?");
        $updateRequest->execute([$request_id]);

        if ($updateRequest->rowCount() > 0) {
            $updatePayment = $pdo->prepare("UPDATE payments SET status = 'Cancelled' WHERE request_id = ?");
            $updatePayment->execute([$request_id]);
            $_SESSION['status'] = 'Request rejected and payment updated.';
        } else {
            $_SESSION['status'] = 'Request ID not found.';
        }
    } else {
        $_SESSION['status'] = 'Invalid action.';
    }

    header('Location: admin_dashboard.php');
    exit;
}
