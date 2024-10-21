<?php
session_start();
include 'database_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "You are not logged in.";
    exit;
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Retrieve user details
try {
    $stmt = $pdo->prepare("SELECT first_name, email, phone FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    
    // Fetch user details
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "User not found.";
        exit;
    }
} catch (PDOException $e) {
    echo "Error retrieving user details: " . $e->getMessage();
    exit;
}

// Process signup logic
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve and sanitize input
    $classID = $_POST['classID'];
    $price = $_POST['price'];
    $paymentMethod = $_POST['paymentMethod'];
    $userName = $user['first_name']; // Use retrieved user name
    $userEmail = $user['email']; // Use retrieved user email
    $userPhone = $user['phone']; // Use retrieved user phone
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    // Simple validation
    if (empty($classID) || empty($price) || empty($start_date) || empty($end_date)) {
        echo "Please fill in all required fields.";
        exit;
    }

    // Prepare SQL statement for signup
    $stmt = $pdo->prepare("INSERT INTO fitness_classes (classID, user_id, price, paymentMethod, first_name, email, phone, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    // Execute the statement with user_id
    if ($stmt->execute([$classID, $user_id, $price, $paymentMethod, $userName, $userEmail, $userPhone, $start_date, $end_date])) {
        // Success
        $_SESSION['success_message'] = "You have successfully signed up for your class!"; // Success message
    } else {
        // Error
        $_SESSION['success_message'] = "Failed to signup, please try again." . $e->getMessage(); // Error
    }
} else {
    echo "Invalid request.";
}
?>
