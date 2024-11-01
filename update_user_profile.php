<?php
session_start();
include 'database_connection.php';
if (!isset($_SESSION['user_logged_in']) || !isset($_SESSION['user_id'])) {
    header('Location: index.php'); 
    exit;
}

// Fetch user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $_SESSION['user_id']]); 
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "User not found.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $weight = $_POST['weight'];
    $height = $_POST['height'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $address = $_POST['address'];

    $stmt = $pdo->prepare("UPDATE users SET first_name = :first_name, last_name = :last_name, email = :email, phone = :phone, weight = :weight, height = :height, city = :city, state = :state, address = :address WHERE id = :id");
    
    try {
        $stmt->execute([
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'phone' => $phone,
            'weight' => $weight,
            'height' => $height,
            'city' => $city,
            'state' => $state,
            'address' => $address,
            'id' => $user_id
        ]);

        $_SESSION['success_message'] = "Profile updated successfully!"; 
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error updating profile: " . $e->getMessage(); 
    }

    header('Location: user_dashboard.php'); 
    exit;
}
?>
