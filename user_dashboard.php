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

// Pagination Variables for Body Data History
$bodyDataLimit = 10; // Number of records per page
$bodyDataPage = isset($_GET['body_data_page']) ? (int)$_GET['body_data_page'] : 1; // Current page number
$bodyDataOffset = ($bodyDataPage - 1) * $bodyDataLimit; // Offset for SQL query

// Fetch body data history with pagination
$bodyDataQuery = 'SELECT bdh.*, u.first_name, u.last_name 
                  FROM body_data_history bdh
                  JOIN users u ON bdh.user_id = u.id
                  ORDER BY bdh.created_at DESC
                  LIMIT :limit OFFSET :offset';
$bodyDataStmt = $pdo->prepare($bodyDataQuery);
$bodyDataStmt->bindParam(':limit', $bodyDataLimit, PDO::PARAM_INT);
$bodyDataStmt->bindParam(':offset', $bodyDataOffset, PDO::PARAM_INT);
$bodyDataStmt->execute();
$bodyDataHistory = $bodyDataStmt->fetchAll(PDO::FETCH_ASSOC);

// Count total body data history records for pagination
$countBodyDataStmt = $pdo->prepare("SELECT COUNT(*) FROM body_data_history WHERE user_id = :user_id");
$countBodyDataStmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
$countBodyDataStmt->execute();
$totalBodyData = $countBodyDataStmt->fetchColumn();
$totalBodyDataPages = ceil($totalBodyData / $bodyDataLimit); // Total number of pages


// Check for request status
$request_status = null;
if (isset($_SESSION['request_status'])) {
    $request_status = $_SESSION['request_status'];
    unset($_SESSION['request_status']); // Clear the session variable
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['submit_body_data'])) {
        $user_id = $_SESSION['user_id'];
        $height = $_POST['height'];
        $weight = $_POST['weight'];
        $bmi = $_POST['bmi'];
        $exercise = $_POST['exercise'];
        $water_consumption = $_POST['water_consumption'];

        // Insert body data into the database
        $stmt = $pdo->prepare('INSERT INTO body_data_history (user_id, height, weight, bmi, exercise, water_consumption) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$user_id, $height, $weight, $bmi, $exercise, $water_consumption]);

        // Set a success message in the session
        $_SESSION['success_message'] = "Data successfully added.";

        // Redirect to the same page
        header('Location: user_dashboard.php'); // Change to your actual file name
        exit; // Ensure no further code is executed
    }
}

// Display success message after redirect
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Clear the session variable
}



// Fetch body data history
$bodyDataQuery = 'SELECT bdh.*, u.first_name, u.last_name 
                  FROM body_data_history bdh
                  JOIN users u ON bdh.user_id = u.id
                  ORDER BY bdh.created_at DESC';
$bodyDataStmt = $pdo->prepare($bodyDataQuery);
$bodyDataStmt->execute();
$bodyDataHistory = $bodyDataStmt->fetchAll(PDO::FETCH_ASSOC);


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

        <!--Display success message-->
        <?php if (isset($success_message)): ?>
        <div class="alert alert-success" id="success-message">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
        <?php endif; ?>


        <div class="success-error-messages">
            <?php if ($request_status === 'success'): ?>
                <div class="success-message">Request successfully submitted!</div>
            <?php elseif ($request_status === 'error'): ?>
                <div class="error-message">Failed to submit request. Please try again.</div>
            <?php endif; ?>
        </div>

        <div class="forms body-form" id="body-data-section">
            <h1>Body Data</h1>
            <form method="POST" action="user_dashboard.php">
                <label for="height">Height (cm):</label>
                <input type="number" name="height" id="height" required>

                <label for="weight">Weight (kg):</label>
                <input type="number" name="weight" id="weight" required>

                <label for="bmi">BMI:</label>
                <input type="number" name="bmi" id="bmi" step="0.01" required>

                <label for="exercise">Exercise (type or duration):</label>
                <input type="text" name="exercise" id="exercise" required>

                <label for="water_consumption">Water Consumption (liters):</label>
                <input type="number" name="water_consumption" id="water_consumption" step="0.01" required>

                <button type="submit" name="submit_body_data">Save</button>
            </form>


        </div>

        <div class="forms nutritionist-form" id="request-nutritionist-section">
            <h1>Request Nutritionist</h1>
            <form method="POST" action="request_nutritionist_user.php">
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
                       <!--Body Data History Table-->
    <div class="body-data-history">
        <h2>Body Data History</h2>
<table>
    <thead>
        <tr>
            <th>User ID</th>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Height (cm)</th>
            <th>Weight (kg)</th>
            <th>BMI</th>
            <th>Exercise</th>
            <th>Water Consumption (liters)</th>
            <th>Date/Time Created</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($bodyDataHistory as $data): ?>
            <tr>
                <td><?php echo htmlspecialchars($data['user_id']); ?></td>
                <td><?php echo htmlspecialchars($data['first_name']); ?></td>
                <td><?php echo htmlspecialchars($data['last_name']); ?></td>
                <td><?php echo htmlspecialchars($data['height']); ?></td>
                <td><?php echo htmlspecialchars($data['weight']); ?></td>
                <td><?php echo htmlspecialchars($data['bmi']); ?></td>
                <td><?php echo htmlspecialchars($data['exercise']); ?></td> <!-- Display exercise -->
                <td><?php echo htmlspecialchars($data['water_consumption']); ?></td> <!-- Display water consumption -->
                <td><?php echo htmlspecialchars($data['created_at']); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

 <!-- Pagination Links for Body Data History -->
    <div class="pagination">
        <?php if ($bodyDataPage > 1): ?>
            <a href="?body_data_page=<?php echo $bodyDataPage - 1; ?>">&laquo; Previous</a>
        <?php endif; ?>
        
        <?php for ($i = 1; $i <= $totalBodyDataPages; $i++): ?>
            <a href="?body_data_page=<?php echo $i; ?>" class="<?php echo ($i === $bodyDataPage) ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
        
        <?php if ($bodyDataPage < $totalBodyDataPages): ?>
            <a href="?body_data_page=<?php echo $bodyDataPage + 1; ?>">Next &raquo;</a>
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

        document.addEventListener('DOMContentLoaded', function() {
        const successMessage = document.getElementById('success-message');
        if (successMessage) {
            setTimeout(() => {
                successMessage.style.opacity = '0'; // Fade out effect
                setTimeout(() => successMessage.remove(), 500); // Remove from DOM after fade out
            }, 3000); // 3-second delay
        }
    });
    </script>
</body>
</html>
