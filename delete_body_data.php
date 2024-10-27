<?php
session_start();
include 'database_connection.php';
if (!isset($_SESSION['user_logged_in']) || !isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Check if ID is set
if (isset($_POST['id'])) {
    $id = $_POST['id'];

    // Prepare and execute delete statement
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

// Redirect back to user dashboard
header('Location: user_dashboard.php');
exit;
?>
