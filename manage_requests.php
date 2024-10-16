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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestId = $_POST['request_id'];
    $action = $_POST['action'];

    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE nutritionist_requests SET status = 'approved' WHERE id = ?");
        $stmt->execute([$requestId]);
        echo "Request approved successfully.";
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE nutritionist_requests SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$requestId]);
        echo "Request rejected successfully.";
    } else {
        echo "Invalid action.";
    }
}
?>
