<?php
session_start();
if (!isset($_SESSION['user_logged_in'])) {
    header('Location: index.php'); // Redirect to login page if not logged in
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="styles.css" />
    <script>
        function loadBlankPage() {
            // Clear the main content area
            document.getElementById("main-content").innerHTML = `
                <div class="blank-content">
                    <h1>Welcome to Your Dashboard!</h1>
                    <p>Your personalized content will appear here.</p>
                </div>
            `;
        }

        // Call the function to load the blank page content
        window.onload = loadBlankPage;
    </script>
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

    <div id="main-content">
        <!-- This will be replaced with blank content -->
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
                    <img src="images/globe_24dp_E8EAED_FILL0_wght400_GRAD0_opsz24.png" alt="Facebook" />
                </a>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; 2010 Huan Fitness Centre | Klang Valley
        </div>
    </div>
</body>
</html>
