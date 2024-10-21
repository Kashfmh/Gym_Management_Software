<?php
session_start();
include 'database_connection.php'; // Adjust the path as necessary

$data = json_decode(file_get_contents("php://input"), true);
$requestId = $data['id'] ?? null;

if ($requestId) {
    // Update the notification to mark it as read
    $stmt = $pdo->prepare("UPDATE nutritionist_requests SET is_read = 1 WHERE id = :id");
    $stmt->bindParam(':id', $requestId, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?>
