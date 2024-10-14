<?php
session_start();
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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['register'])) {
        // Registration logic
        $fullname = $_POST['fullname'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $weight = $_POST['weight'];
        $height = $_POST['height'];

        $stmt = $pdo->prepare('INSERT INTO users (fullname, email, phone, password, weight, height) VALUES (?, ?, ?, ?, ?, ?)');
        
        try {
            $stmt->execute([$fullname, $email, $phone, $password, $weight, $height]);
            $register_success = "Registration successful!";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Integrity constraint violation
                $register_error = "Email already exists.";
            } else {
                $register_error = "Error: " . $e->getMessage();
            }
        }
    }

    if (isset($_POST['login'])) {
        // Login logic
        $email = $_POST['email'];
        $password = $_POST['password'];

        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_logged_in'] = true;
            $_SESSION['fullname'] = $user['fullname'];
            header('Location: user_dashboard.php');
            exit;
        } else {
            $login_error = "Invalid email or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Huan Fitness Centre</title>
    <link rel="stylesheet" href="styles.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Protest+Strike&display=swap" rel="stylesheet" />
    <script src="script.js"></script>
</head>
<body>
    <div class="header">
        <div class="left-section">
            <img src="images/barbell-7834321_640-removebg-preview.png" width="100px" alt="Huan Fitness Centre logo" /><a href="#home"> HUAN FITNESS CENTRE</a>
        </div>
        <div class="middle-section">
            <div class="extra">
                <div><a href="#services-section">Services</a></div>
                <div><a href="#pricing-section">Pricing</a></div>
                <div><a href="#support-section">Support</a></div>
                <div><a href="#Contact-section">Contact</a></div>
                <div><a href="#login-section">Login</a></div>
            </div>
        </div>
        <div class="right-section">
            <div class="admin-btn">
                <button id="admin-button" onclick="showAdminLoginForm()">Admin</button>
            </div>
        </div>
    </div>
    <div class="main">
        <div class="introduction" id="home">
            <div class="one">EASY TO USE GYM MANAGEMENT<br />SOFTWARE</div>
            <div class="two">Built to make your<br />life easier.</div>
            <div class="three">
                The best all-in-1 gym management software<br />
                with multiple services such as dietary<br />consultation, weight
                management and<br />much more! What makes us different<br />from the
                rest? double the services at<br />fraction of the cost. Better for
                you,<br />members, and your wallet.
            </div>
        </div>
    </div>
    <div class="services" id="services-section">
        <div class="service-card">
            <h3>Physical Training</h3>
            <p>Get personalized training programs.</p>
        </div>
        <div class="service-card">
            <h3>Dietary Consultation</h3>
            <p>Receive expert dietary advice.</p>
        </div>
        <div class="service-card">
            <h3>Much More</h3>
            <p>Explore a variety of fitness services.</p>
        </div>
    </div>

    <div class="pricing" id="pricing-section">
        <h2>Pricing</h2>
        <div class="pricing-cards">
            <div class="pricing-card">
                <h3>Membership</h3>
                <p>Monthly Fee: RM50 - RM100</p>
                <p>Access to all fitness classes.</p>
            </div>
            <div class="pricing-card">
                <h3>Nutrition Consultation</h3>
                <p>Fee: RM20 per session</p>
                <p>Personalized dietary advice.</p>
            </div>
        </div>
    </div>
<!--git test-->
    <div class="login-main" id="login-section">
        <div class="login-box" id="login-form">
            <div class="login-text">Login Form</div>
            <div class="login-info">
                <?php if (!empty($login_error)) echo "<p style='color: red;'>$login_error</p>"; ?>
                <form method="POST" action="">
                    <div class="input-group">
                        <input type="email" name="email" class="input-field" placeholder="Email" required />
                    </div>
                    <div class="input-group">
                        <input type="password" name="password" class="input-field" placeholder="Password" required />
                    </div>
                    <a href="#" class="forgot-password">Forgot password?</a>
                    <button type="submit" name="login" class="login-button">Login</button>
                </form>
                <div class="signup-text">
                    Not a member?
                    <a href="#" onclick="showRegisterForm()">Signup now</a>
                </div>
            </div>
        </div>

        <div class="login-box" id="register-form" style="display: none">
            <div class="login-text">Register Form</div>
            <div class="login-info">
                <?php if (!empty($register_error)) echo "<p style='color: red;'>$register_error</p>"; ?>
                <?php if (!empty($register_success)) echo "<p style='color: green;'>$register_success</p>"; ?>
                <form method="POST" action="">
                    <div class="input-group">
                        <input type="text" name="fullname" class="input-field" placeholder="Full Name" required />
                    </div>
                    <div class="input-group">
                        <input type="email" name="email" class="input-field" placeholder="Email" required />
                    </div>
                    <div class="input-group">
                        <input type="text" name="phone" class="input-field" placeholder="Phone Number" required />
                    </div>
                    <div class="input-group">
                        <input type="password" name="password" class="input-field" placeholder="Password" required />
                    </div>
                    <div class="input-group">
                        <input type="number" name="weight" class="input-field" placeholder="Weight (kg)" />
                    </div>
                    <div class="input-group">
                        <input type="number" name="height" class="input-field" placeholder="Height (cm)" />
                    </div>
                    <button type="submit" name="register" class="login-button">Register</button>
                </form>
                <div class="signup-text">
                    Already a member?
                    <a href="#" onclick="showLoginForm()">Login here</a>
                </div>
            </div>
        </div>
    </div>
    <div class="footer">
        <div class="footer-content">
            <div class="footer-section about">
                <h3>About Us</h3>
                <p>
                    Huan Fitness Centre is dedicated to providing top-notch fitness
                    services to help you achieve your health goals.
                </p>
            </div>
            <div class="footer-section contact">
                <h3>Contact Us</h3>
                <p>Email: huanfitnesspals@gmail.com</p>
                <p>Phone: +60 1123776041</p>
            </div>
            <div class="footer-section social">
                <h3>Follow Us</h3>
                <a href="#">
                    <img src="images/globe_24dp_E8EAED_FILL0_wght400_GRAD0_opsz24.png" alt="Socials" />
                </a>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; 2010 Huan Fitness Centre | Klang Valley
        </div>
    </div>
</body>
</html>
