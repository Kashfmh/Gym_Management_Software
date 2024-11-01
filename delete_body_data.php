<?php
session_start();
include 'database_connection.php';
if (!isset($_SESSION['user_logged_in']) || !isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}


if (isset($_POST['id'])) {
    $id = $_POST['id'];

    
    $stmt = $pdo->prepare("DELETE FROM body_data_history WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Body data successfully deleted.";
    } else {
        $_SESSION['error_message'] = "Failed to delete body data.";
    }
} else {
    $_SESSION['error_message'] = "Invalid request.";
}


header('Location: user_dashboard.php');
exit;
?>
