<?php
session_start();
require 'database_connection.php'; // Include your database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $mobile = $_POST['mobile'];
    $password = $_POST['password'];

    // Fetch the current admin data
    $admin_id = $_SESSION['admin_id']; // Assuming the admin ID is stored in the session

    // Prepare the SQL query
    $sql = "UPDATE admins SET name = ?, email = ?, mobile = ?";

    // Check if a new password was provided
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql .= ", password = ?";
    }

    $sql .= " WHERE id = ?";

    // Execute the query
    $stmt = $pdo->prepare($sql);
    if (!empty($password)) {
        $stmt->execute([$name, $email, $mobile, $hashed_password, $admin_id]);
    } else {
        $stmt->execute([$name, $email, $mobile, $admin_id]);
    }

    // Redirect back to the profile page
    header('Location: admin_dashboard.php');
    exit;
}
?>
