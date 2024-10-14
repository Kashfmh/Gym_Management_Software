<?php
session_start();

if (!isset($_SESSION['user_logged_in']) || !isset($_SESSION['user_id'])) {
    header('Location: index.php'); // Redirect to login page if not logged in
    exit;
}

// Database connection
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

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "User not found.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="user_dashboard.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Protest+Strike&display=swap" rel="stylesheet" />
    <script src="script.js" defer></script>
</head>
<body>
    <div class="header">
        <div class="left-section">
            <div class="hamburger"><img src="images/menu_24dp_E8EAED_FILL0_wght400_GRAD0_opsz24.png" width="40px" style="margin-right: 20px;"/>Menu</div>
            <img src="images/barbell-7834321_640-removebg-preview.png" width="100px" alt="Huan Fitness Centre logo" /><a href="#home"> HUAN FITNESS PALS</a>
        </div>
        
        <div class="right-section">
            <div class="user-profile">
                <button class="profile-btn"><img src="images/account_circle_24dp_E8EAED_FILL0_wght400_GRAD0_opsz24.png" height="40px"/></button>
            </div>
        </div>
    </div>

    <div class="left-nav">
        <a href="#dashboard">Dashboard</a>
        <a href="#profile">Profile</a>
        <a href="#settings">Settings</a>
        <a href="#logout">Logout</a>
    </div>

    <div id="main-content" class="main">
        <div class="content">
            <div class="welcome-message">
                <h1>Welcome, <?php echo htmlspecialchars($user['first_name']); ?>!</h1>
            </div>
            <div class="forms">
                <div class="body-form">
                    <form method="POST" action="manage_body_data.php">
                        <input type="number" name="weight" placeholder="Weight (kg)" required>
                        <input type="text" name="exercise" placeholder="Exercise Details" required>
                        <input type="number" name="water_consumption" placeholder="Water (liters)" required>
                        <input type="date" name="date" required>
                        <button type="submit" name="save_body_data">Save</button>
                    </form>
                </div>
                <div class="nutritionist-form">
                    <form method="POST" action="request_nutritionist.php">
                        <input type="date" name="preferred_date" required>
                        <input type="time" name="preferred_time" required>
                        <button type="submit" name="request_meeting">Request Meeting</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- User Profile Section -->
    <div id="profile-section" style="display: none;">
        <div class="profile-header">
            <img src="images/account_circle_24dp_E8EAED_FILL0_wght400_GRAD0_opsz24.png" alt="User Avatar" />
            <h2 id="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h2>
            <p><?php echo htmlspecialchars($user['email']); ?></p>
            <button onclick="editProfile()">Edit</button>
        </div>
        <div class="profile-details">
            <p><strong>First Name:</strong> <span id="first-name"><?php echo htmlspecialchars($user['first_name']); ?></span></p>
            <p><strong>Last Name:</strong> <span id="last-name"><?php echo htmlspecialchars($user['last_name']); ?></span></p>
            <p><strong>Email:</strong> <span id="email"><?php echo htmlspecialchars($user['email']); ?></span></p>
            <p><strong>Phone:</strong> <span id="phone"><?php echo htmlspecialchars($user['phone']); ?></span></p>
            <p><strong>Weight:</strong> <span id="weight"><?php echo htmlspecialchars($user['weight']); ?></span></p>
            <p><strong>Height:</strong> <span id="height"><?php echo htmlspecialchars($user['height']); ?></span></p>
            <p><strong>City:</strong> <span id="city"><?php echo htmlspecialchars($user['city']); ?></span></p>
            <p><strong>State:</strong> <span id="state"><?php echo htmlspecialchars($user['state']); ?></span></p>
            <p><strong>Address:</strong> <span id="address"><?php echo htmlspecialchars($user['address']); ?></span></p>
        </div>

        <!-- Logout Button -->
        <form method="POST" action="logout.php">
            <button type="submit" class="logout-button">Logout</button>
        </form>
    </div>

    <!-- Edit Profile Form -->
    <div id="edit-profile-form" style="display: none;">
        <form method="POST" action="update_profile.php">
            <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required />
            <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required />
            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required />
            <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" />
            <input type="number" name="weight" value="<?php echo htmlspecialchars($user['weight']); ?>" />
            <input type="number" name="height" value="<?php echo htmlspecialchars($user['height']); ?>" />
            <input type="text" name="city" value="<?php echo htmlspecialchars($user['city']); ?>" required />
            <input type="text" name="state" value="<?php echo htmlspecialchars($user['state']); ?>" required />
            <textarea name="address" required><?php echo htmlspecialchars($user['address']); ?></textarea>
            <button type="submit" id="user-btn">Update</button>
            <button type="button" id="user-btn" onclick="cancelEditProfile()">Cancel</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const profileBtn = document.querySelector('.profile-btn');
            const profileSection = document.getElementById('profile-section');
            const mainContent = document.getElementById('main-content');
            const editProfileForm = document.getElementById('edit-profile-form');

            profileBtn.addEventListener('click', function() {
                if (profileSection.style.display === 'none' || profileSection.style.display === '') {
                    profileSection.style.display = 'block';
                    mainContent.style.display = 'none';
                } else {
                    profileSection.style.display = 'none';
                    mainContent.style.display = 'block';
                }
            });

            window.editProfile = function() {
                editProfileForm.style.display = 'block';
                profileSection.style.display = 'none';
            };

            window.cancelEditProfile = function() {
                editProfileForm.style.display = 'none';
                profileSection.style.display = 'block';
            };
        });
    </script>
</body>
</html>
