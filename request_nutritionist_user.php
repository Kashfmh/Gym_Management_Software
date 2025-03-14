<?php
session_start();
require 'database_connection.php';
if (!isset($_SESSION['user_logged_in'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $preferred_date = $_POST['preferred_date'];
    $preferred_time = $_POST['preferred_time'];
    $user_id = $_POST['user_id'];
    $payment_method = $_POST['payment_method']; 

    $today = date('Y-m-d');

    // Validate the preferred date
    if ($preferred_date <= $today) {
        $_SESSION['request_status'] = 'error'; 
        header('Location: user_dashboard.php'); 
        exit;
    }

    

    // Insert the request into the database
    $stmt = $pdo->prepare("INSERT INTO nutritionist_requests (user_id, preferred_date, preferred_time, payment_method) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $preferred_date, $preferred_time, $payment_method]);

    $request_id = $pdo->lastInsertId(); 

    if (!$request_id) {
    die('Failed to retrieve request ID.');
}


    // Insert payment record with the captured request_id
$amount = 20.00;
$paymentStmt = $pdo->prepare("INSERT INTO payments (user_id, amount, payment_method, payment_date, status, request_id) VALUES (?, ?, ?, ?, ?, ?)");
$paymentStmt->execute([$user_id, $amount, $payment_method, $preferred_date, 'Pending', $request_id]);


// Redirect to the same page to avoid resubmission
$_SESSION['success_message'] = "Nutritionist request submitted successfully.";
header('Location: user_dashboard.php');
exit;
}
?>
