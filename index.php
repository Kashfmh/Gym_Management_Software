<?php
session_start();
include 'database_connection.php';

    //User login 
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];

        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['fullname'] = $user['first_name'] . ' ' . $user['last_name'];
            header('Location: user_dashboard.php');
            exit;
        } else {
            $login_error = "Invalid email or password.";
        }
    }

    //User registration logic
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
            // Collect registration data
            $first_name = $_POST['first_name'];
            $last_name = $_POST['last_name'];
            $email = $_POST['email'];
            $phone = $_POST['phone'];
            $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $weight = $_POST['weight'];
            $height = $_POST['height'];
            $city = $_POST['city'];
            $state = $_POST['state'];
            $address = $_POST['address'];

            // Check for existing email or phone
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? OR phone = ?');
            $stmt->execute([$email, $phone]);
            $existing_user = $stmt->fetch();

            if ($existing_user) {
                if ($existing_user['email'] === $email) {
                    $register_error = "Email already exists.";
                }
                if ($existing_user['phone'] === $phone) {
                    $register_error = "Phone number already exists.";
                }
            } else {
                // Proceed with registration
                $stmt = $pdo->prepare('INSERT INTO users (first_name, last_name, email, phone, password, weight, height, city, state, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                
                try {
                    $stmt->execute([$first_name, $last_name, $email, $phone, $password, $weight, $height, $city, $state, $address]);
                    $_SESSION['user_logged_in'] = true;
                    $_SESSION['user_id'] = $pdo->lastInsertId();
                    $_SESSION['fullname'] = $first_name . ' ' . $last_name;
                    $register_success = "Registration successful! You can now log in.";
                    header('Location: login.php'); // Redirect to login page
                    exit;
                } catch (PDOException $e) {
                    $register_error = "Error: " . $e->getMessage();
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
    <link rel="stylesheet" href="index.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Protest+Strike&display=swap" rel="stylesheet" />
</head>
<script>
        function showRegisterForm() {
  event.preventDefault();
  document.getElementById("login-form").style.display = "none";
  document.getElementById("register-form").style.display = "block";
}

function showLoginForm() {
  event.preventDefault();
  document.getElementById("register-form").style.display = "none";
  document.getElementById("login-form").style.display = "block";
}

function goBackToHomepage() {
  location.reload();
}

document.addEventListener("DOMContentLoaded", function () {
  const registerForm = document.getElementById("register-form");
  if (registerForm && window.location.hash === "#register-form") {
    registerForm.scrollIntoView();
  }
});
    </script>
<body>
    <div class="header">
        <div class="left-section">
            <img src="images/barbell-7834321_640-removebg-preview.png" width="100px" alt="Huan Fitness Centre logo" /><a href="#home-section"><span class="mobile">HUAN FITNESS PALS</span></a>
        </div>
        <div class="middle-section">
            <div class="extra">
                <div><a href="#services-section">Services</a></div>
                <div><a href="#pricing-section">Pricing</a></div>
                <div><a href="#login-section">Login</a></div>
                <div><a href="#details-section">Details</a></div>
                
            </div>
        </div>
        <div class="right-section">
            <div class="admin-btn">
                <button id="admin-button"><a href="admin.php">Admin</a></button>
            </div>
        </div>
    </div>
    <div class="main">
        <div class="introduction" id="home-section">
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
    <span class="services-text">SERVICES</span>
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
            <h3>Sign Up for Classes</h3>
            <p>Join our engaging classes to enhance your fitness journey!</p>
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
                    <a href="forgot_password.php" class="forgot-password">Forgot password?</a>
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
                <input type="text" name="first_name" class="input-field" placeholder="First Name" required />
            </div>
            <div class="input-group">
                <input type="text" name="last_name" class="input-field" placeholder="Last Name" required />
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
            <div class="input-group">
                <input type="text" name="city" class="input-field" placeholder="City" required />
            </div>
            <div class="input-group">
                <input type="text" name="state" class="input-field" placeholder="State" required />
            </div>
            <div class="input-group">
                <textarea name="address" class="input-field" placeholder="Address" required></textarea>
            </div>
            
            <button type="submit" name="register" class="register-button">Register</button>
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
                <p>
                    Our mission is to promote good health and well-being by offering
                    comprehensive tools and resources through HuanFitnessPal, in alignment
                    with the United Nations Sustainable Development Goal 3.
                </p>
            </div>
            <div class="footer-section contact">
                <h3>Contact Us</h3>
                <p>Email: huanfitnesscentre@gmail.com</p>
                <p>Phone: +60 1123776041</p>
            </div>
            <div class="footer-section social">
                <h3>Follow Us</h3>
                <a href="#">
                    <img src="images/globe_24dp_E8EAED_FILL0_wght400_GRAD0_opsz24.png" alt="Socials" />
                </a>
            </div>
        </div>
        <div class="footer-section sdg3">
            <h3>SDG 3: Good Health and Well-being</h3>
            <p>
                We are committed to ensuring healthy lives and promoting well-being for all
                at all ages. Our platform provides tools to help you monitor and improve
                your health effectively.
            </p>
        </div>
        <div class="footer-bottom" id="details-section">
            &copy; 2010 Huan Fitness Centre | Klang Valley
        </div>
    </div>
</body>
</html>