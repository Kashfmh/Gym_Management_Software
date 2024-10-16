<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || !isset($_SESSION['user_id'])) {
    header('Location: index.php');
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

// Fetch user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "User not found.";
    exit;
}

// Pagination Variables
$limit = 10; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page number
$offset = ($page - 1) * $limit; // Offset for SQL query

// Fetch request history with pagination
$stmt = $pdo->prepare("SELECT preferred_date, preferred_time, status FROM nutritionist_requests WHERE user_id = :user_id ORDER BY id DESC LIMIT :limit OFFSET :offset");
$stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count total requests for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM nutritionist_requests WHERE user_id = :user_id");
$countStmt->execute(['user_id' => $_SESSION['user_id']]);
$totalRequests = $countStmt->fetchColumn();
$totalPages = ceil($totalRequests / $limit); // Total number of pages

// Check for request status
$request_status = null;
if (isset($_SESSION['request_status'])) {
    $request_status = $_SESSION['request_status'];
    unset($_SESSION['request_status']); // Clear the session variable
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
    <script src="script.js" defer></script>
</head>
<body>
    <div class="header">
        <div class="left-section">
            <img src="images/barbell-7834321_640-removebg-preview.png" width="100px" alt="Huan Fitness Centre logo" /><a href="#home"> HUAN FITNESS PALS</a>
        </div>
        <div class="middle-section">
            <div class="extra">
                <div><a href="#body-data-section">Body Data</a></div>
                <div><a href="#request-nutritionist-section">Request Nutritionist</a></div>
                <div><a href="#request-history-section">Request History</a></div>
                <div><a href="#body-data-history-section">Body Data History</a></div>
            </div>
        </div>
        
        <div class="right-section">
            <div class="user-profile">
                <button class="profile-btn"><img src="images/white_account.png" height="40px"/></button>
            </div>
        </div>
    </div>

    <div id="main-content" class="main">
        <div class="welcome-message">
            <h1>Welcome, <?php echo htmlspecialchars($user['first_name']); ?>!</h1>
        </div>

        <div class="success-error-messages">
            <?php if ($request_status === 'success'): ?>
                <div class="success-message">Request successfully submitted!</div>
            <?php elseif ($request_status === 'error'): ?>
                <div class="error-message">Failed to submit request. Please try again.</div>
            <?php endif; ?>
        </div>

        <div class="forms body-form" id="body-data-section">
            <h1>Body Data</h1>
            <form method="POST" action="manage_body_data.php">
                <input type="number" name="weight" placeholder="Achieve Weight (kg)" required>
                <input type="text" name="exercise" placeholder="Exercise Details" required>
                <input type="number" name="water_consumption" placeholder="Water (liters)" required>
                <input type="date" name="date" required>
                <button type="submit" name="save_body_data">Save</button>
            </form>
        </div>

        <div class="forms nutritionist-form" id="request-nutritionist-section">
            <h1>Request Nutritionist</h1>
            <form method="POST" action="request_nutritionist.php">
                <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id']; ?>">
                <input type="date" name="preferred_date" required>
                <input type="time" name="preferred_time" required>
                <button type="submit" name="request_meeting">Request Meeting</button>
            </form>
        </div>

        <div class="request-history" id="request-history-section">
            <h1>Request History</h1>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $request): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($request['preferred_date']); ?></td>
                            <td><?php echo htmlspecialchars($request['preferred_time']); ?></td>
                            <td><?php echo htmlspecialchars($request['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>">&laquo; Previous</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" class="<?php echo ($i === $page) ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>">Next &raquo;</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- User Profile Section -->
    <div id="profile-section" style="display: none;">
        <div class="profile-header">
            <img src="images/account_circle_24dp_434343_FILL0_wght400_GRAD0_opsz24.png" alt="User Avatar" />
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

        <!-- Go Back to Homepage Button -->
        <button onclick="goBackToHomepage()" style="margin-top: 10px;">Go Back to Homepage</button>

        <!-- Logout Button -->
        <form method="POST" action="logout.php">
            <button type="submit" class="logout-button">Logout</button>
        </form>
    </div>

    <!-- Edit Profile Form -->
    <div id="edit-profile-form" style="display: none;">
        <form method="POST" action="update_profile.php">
            <strong>Name:</strong>
            <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required />
            <strong>Last Name:</strong>
            <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required />
            <strong>Email:</strong>
            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required />
            <strong>Phone Number:</strong>
            <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" />
            <strong>Weight:</strong>
            <input type="number" name="weight" value="<?php echo htmlspecialchars($user['weight']); ?>" />
            <strong>Height:</strong>
            <input type="number" name="height" value="<?php echo htmlspecialchars($user['height']); ?>" />
            <strong>City:</strong>
            <input type="text" name="city" value="<?php echo htmlspecialchars($user['city']); ?>" required />
            <strong>State:</strong>
            <input type="text" name="state" value="<?php echo htmlspecialchars($user['state']); ?>" required />
            <strong>Address:</strong>
            <textarea name="address" required><?php echo htmlspecialchars($user['address']); ?></textarea>
            <button type="submit" id="user-btn">Update</button>
            <button type="button" id="cancel-btn" onclick="cancelEditProfile()">Cancel</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const profileBtn = document.querySelector('.profile-btn');
            const profileSection = document.getElementById('profile-section');
            const mainContent = document.getElementById('main-content');
            const editProfileForm = document.getElementById('edit-profile-form');

            profileBtn.addEventListener('click', function() {
                profileSection.style.display = 'block';
                mainContent.style.display = 'none';
            });

            window.editProfile = function() {
                editProfileForm.style.display = 'block';
                profileSection.style.display = 'none';
            };

            window.cancelEditProfile = function() {
                editProfileForm.style.display = 'none';
                profileSection.style.display = 'block';
            };

            window.goBackToHomepage = function() {
                profileSection.style.display = 'none';
                mainContent.style.display = 'flex';
            };
        });

        function hideMessages() {
            const successMessage = document.querySelector('.success-message');
            const errorMessage = document.querySelector('.error-message');

            if (successMessage) {
                setTimeout(() => {
                    successMessage.style.opacity = '0';
                    setTimeout(() => successMessage.remove(), 500); // Remove from DOM after fade out
                }, 3000); // 3-second delay
            }

            if (errorMessage) {
                setTimeout(() => {
                    errorMessage.style.opacity = '0';
                    setTimeout(() => errorMessage.remove(), 500); // Remove from DOM after fade out
                }, 3000); // 3-second delay
            }
        }

        // Call the function on page load
        window.onload = hideMessages;
    </script>
</body>
</html>
