<?php
session_start();

if (!isset($_SESSION['user_logged_in']) || !isset($_SESSION['user_id'])) {
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
    die("Could not connect to the database $db :" . $e->getMessage());
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $preferred_date = $_POST['preferred_date'];
    $preferred_time = $_POST['preferred_time'];
    $user_id = $_POST['user_id'];

    // Get today's date
    $today = date('Y-m-d');

    // Validate the preferred date
    if ($preferred_date < $today) {
        $_SESSION['request_status'] = 'error'; // Set error status
        header('Location: user_dashboard.php'); // Redirect back to dashboard
        exit;
    }

    // Insert the request into the database if the date is valid
    $stmt = $pdo->prepare("INSERT INTO nutritionist_requests (user_id, preferred_date, preferred_time, status) VALUES (:user_id, :preferred_date, :preferred_time, 'pending')");
    $stmt->execute([
        'user_id' => $user_id,
        'preferred_date' => $preferred_date,
        'preferred_time' => $preferred_time
    ]);

    $_SESSION['request_status'] = 'success'; // Set success status
    header('Location: user_dashboard.php'); // Redirect back to dashboard
    exit;
}
?>
