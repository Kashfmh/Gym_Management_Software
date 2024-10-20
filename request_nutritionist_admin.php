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
    die("Could not connect to the database $db :" . $e->getMessage());
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_meeting'])) {
    // Get form data
    $user_id = $_POST['user_id'];
    $preferred_date = $_POST['preferred_date'];
    $preferred_time = $_POST['preferred_time'];
    $payment_method = $_POST['payment_method'];


    // Get today's date
    $today = date('Y-m-d');

    // Validate the preferred date
    if ($preferred_date <= $today) {
        $_SESSION['request_status'] = 'error'; // Set error status
        header('Location: admin_dashboard.php'); // Redirect back to admin dashboard
        exit;
    }



// Insert into nutritionist_requests table
    $stmt = $pdo->prepare("INSERT INTO nutritionist_requests (user_id, preferred_date, preferred_time, payment_method) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $preferred_date, $preferred_time, $payment_method]);

    
// Capture the last inserted request_id
$request_id = $pdo->lastInsertId(); 


// Insert payment record with the captured request_id
$amount = 20.00; // Fixed amount for each session
    $paymentStmt = $pdo->prepare("INSERT INTO payments (user_id, amount, payment_method, payment_date, status, request_id) VALUES (?, ?, ?, ?, ?, ?)");
    $paymentStmt->execute([$user_id, $amount, $payment_method, $preferred_date, 'Pending', $request_id]);

$_SESSION['request_status'] = 'success'; // Set success status
// Redirect to the same page to avoid resubmission
header('Location: admin_dashboard.php');
exit;

}
?>
