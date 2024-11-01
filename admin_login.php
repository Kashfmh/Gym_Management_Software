<?php
session_start();
include 'database_connection.php';


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['admin_login'])) {
    $admin_email = $_POST['admin_email'];
    $admin_password = $_POST['admin_password'];


    $stmt = $pdo->prepare('SELECT * FROM admins WHERE email = ?');
    $stmt->execute([$admin_email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($admin_password, $admin['password'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $admin['id'];
        header('Location: admin_dashboard.php');
        exit;
    } else {
        $admin_login_error = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="styles/admin.css">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Protest+Strike&display=swap" rel="stylesheet" />
</head>
<body>
    <div class="login-box-admin">
        <form method="POST" action="">
            <span class="admin-txt">ADMIN LOGIN FORM</span>
            <div class="input-group">
                <input type="email" name="admin_email" class="input-field" placeholder="Email" required />
            </div>
            <div class="input-group">
                <input type="password" name="admin_password" class="input-field" placeholder="Password" required />
            </div>
            <button type="submit" name="admin_login" class="login-button-admin">Login</button>
        </form>
        <div class="back-to-homepage-text">
            <a href="index.php">Go back to homepage</a>
        </div>
        <?php if (!empty($admin_login_error)) echo "<p style='color: red;'>$admin_login_error</p>"; ?>
    </div>
</body>
</html>
