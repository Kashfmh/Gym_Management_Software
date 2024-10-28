<?php
session_start();
require 'database_connection.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Validate the token
    $stmt = $pdo->prepare("SELECT email FROM password_resets WHERE token = :token");
    $stmt->execute(['token' => $token]);
    $resetRequest = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$resetRequest) {
        die("Invalid token.");
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $newPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $email = $resetRequest['email'];

        
        $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE email = :email");
        $stmt->execute(['password' => $newPassword, 'email' => $email]);

        
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = :token");
        $stmt->execute(['token' => $token]);

        header('Location: index.php');
        exit;
    }
} else {
    die("No token provided.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link rel="stylesheet" href="styles/reset_password.css">
</head>
<body>
    <div class="main">
        <div class="form-container">
            <h1>Reset Password</h1>
            <form method="POST">
                <div class="input-group">
                    <input type="password" name="password" class="input-field" placeholder="New Password" required>
                </div>
                <button type="submit" class="submit-button">Reset Password</button>
            </form>
        </div>
    </div>
</body>
</html>

