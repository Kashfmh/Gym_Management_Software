<?php
session_start();
require 'database_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $requestId = $data['id'];

    $stmt = $pdo->prepare("UPDATE nutritionist_requests SET is_read = 1 WHERE id = :id AND user_id = :user_id");
    $stmt->execute([
        'id' => $requestId,
        'user_id' => $_SESSION['user_id']
    ]);

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>
