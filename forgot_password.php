<?php
session_start();
include 'database_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];

    //validattion of email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {

        //token generation
        $token = bin2hex(random_bytes(50));
        $stmt = $pdo->prepare("INSERT INTO password_resets (email, token) VALUES (:email, :token)");
        $stmt->execute(['email' => $email, 'token' => $token]);

        //email
        $resetLink = "http://localhost/phpnewassignment/reset_password.php?token=" . $token;

         //email body and extra stuff
    if (mail($email, "Password Reset Link", "Hello fellow member,\n\nHere is the password reset link you requested! Please click the link to continue with your password reset:\n" . $resetLink)) {
        $_SESSION['message'] = "A password reset link has been sent to your email.";
    } else {
        $_SESSION['error'] = "Failed to send the reset email. Please try again later.";
    }
        header('Location: forgot_password.php');
        exit;
    } else {
        $_SESSION['error'] = "Email not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="styles/forgot_password.css">
</head>
<body>
    <div class="main">
        <div class="form-container">
            <h1>Forgot Password</h1>
            <form method="POST">
                <div class="input-group">
                    <input type="email" name="email" class="input-field" placeholder="Enter your email" required>
                </div>
                <button type="submit" class="submit-button">Send Reset Link</button>
                <a href="index.php" class="back-to-homepage">Back to Homepage</a>
            </form>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error-message"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['message'])): ?>
                <div class="success-message"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

