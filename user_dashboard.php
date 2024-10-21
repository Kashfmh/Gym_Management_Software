<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || !isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Database connection
require 'database_connection.php'; // Include your database connection

// Fetch user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "User not found.";
    exit;
}

// Set up userID
$userId = $_SESSION['user_id'];

// Pagination Variables
$limit = 10; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page number
$offset = ($page - 1) * $limit; // Offset for SQL query

$stmt = $pdo->prepare("SELECT r.id, r.preferred_date, r.preferred_time, r.payment_method, r.status, p.status AS payment_status 
                        FROM nutritionist_requests r
                        LEFT JOIN payments p ON r.id = p.request_id 
                        WHERE r.user_id = :user_id 
                        ORDER BY r.id DESC 
                        LIMIT :limit OFFSET :offset");
$stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Count total requests for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM nutritionist_requests WHERE user_id = :user_id");
$countStmt->execute(['user_id' => $userId]);
$totalRequests = $countStmt->fetchColumn();

$totalPages = ceil($totalRequests / $limit); // Total number of pages


// Fetch body data history for the logged-in user with pagination
$bodyDataLimit = 10; // Number of records per page
$bodyDataPage = isset($_GET['body_data_page']) ? (int)$_GET['body_data_page'] : 1; // Current page number
$bodyDataOffset = ($bodyDataPage - 1) * $bodyDataLimit; // Offset for SQL query

$bodyDataQuery = 'SELECT bdh.*, u.first_name, u.last_name 
                  FROM body_data_history bdh
                  JOIN users u ON bdh.user_id = u.id
                  WHERE bdh.user_id = :user_id
                  ORDER BY id DESC
                  LIMIT :limit OFFSET :offset';

$bodyDataStmt = $pdo->prepare($bodyDataQuery);
$bodyDataStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
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

// Handle body data submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_body_data'])) {
    $user_id = $_SESSION['user_id'];
    $height = $_POST['height'] ?? null;
    $weight = $_POST['weight'] ?? null;
    $bmi = $_POST['bmi'] ?? null;
    $exercise = $_POST['exercise'] ?? null;
    $water_consumption = $_POST['water_consumption'] ?? null;

    if ($height && $weight && $exercise && $water_consumption) {
        // Insert body data into the database
        $stmt = $pdo->prepare('INSERT INTO body_data_history (user_id, height, weight, bmi, exercise, water_consumption) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$user_id, $height, $weight, $bmi, $exercise, $water_consumption]);

        // Set a success message in the session
        $_SESSION['success_message'] = "Data successfully added.";

        // Redirect to the same page to prevent resubmission
        header('Location: user_dashboard.php');
        exit;
    } else {
        $_SESSION['success_message'] = "Please fill in all required fields.";
        header('Location: user_dashboard.php');
        exit;
    }
}

// Handle class signup submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['classID'])) {
    $classID = trim($_POST['classID'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $paymentMethod = trim($_POST['paymentMethod'] ?? '');
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date = trim($_POST['end_date'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    // Validation checks
    if (empty($classID) || empty($price) || empty($paymentMethod) || empty($start_date) || empty($end_date) || empty($first_name) || empty($email) || empty($phone)) {
        $_SESSION['success_message'] = "Please fill in all required fields.";
    } elseif (!is_numeric($price) || $price <= 0) {
        $_SESSION['success_message'] = "Price must be a positive number.";
    } elseif ($start_date < date('Y-m-d')) {
        $_SESSION['success_message'] = "Start date cannot be before today.";
    } elseif ($end_date <= $start_date) {
        $_SESSION['success_message'] = "End date must be after the start date.";
    } else {
        // Check for existing enrollment
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM fitness_classes WHERE user_id = ? AND classID = ? AND (start_date < ? AND end_date > ?)");
        $stmt->execute([$userId, $classID, $end_date, $start_date]);
        $existingCount = $stmt->fetchColumn();

        if ($existingCount > 0) {
            $_SESSION['success_message'] = "You are already signed up for this class during the selected dates.";
        } else {
            // Prepare SQL statement for signup, including first_name, email, and phone
            $stmt = $pdo->prepare("INSERT INTO fitness_classes (classID, user_id, price, paymentMethod, start_date, end_date, first_name, email, phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$classID, $userId, $price, $paymentMethod, $start_date, $end_date, $first_name, $email, $phone])) {
                $_SESSION['success_message'] = "Thank you for signing up! You have successfully registered for the " . htmlspecialchars($classID) . " class.";
            } else {
                $_SESSION['success_message'] = "There was an error processing your signup. Please try again.";
            }
        }
    }
    // Redirect to prevent resubmission
    header('Location: user_dashboard.php');
    exit;
}

// Display success message after redirect
$success_message = $_SESSION['success_message'] ?? null;
unset($_SESSION['success_message']);

$paymentMethodMapping = [
    'credit_card' => 'Credit Card',
    'cash' => 'Cash',
    'bank_transfer' => 'Bank Transfer',
    'e_wallet' => 'E-Wallet'
];
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="styles/user_dashboard.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet" />
    
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

         <!-- Display upcoming requests alert -->
          <div class="inbox">
            <h1>Inbox</h1>
        <?php
            $upcomingRequestsStmt = $pdo->prepare
            ("
                SELECT id, preferred_date, preferred_time 
                FROM nutritionist_requests 
                WHERE user_id = :user_id 
                AND preferred_date >= CURDATE() 
                AND (preferred_date > CURDATE() OR (preferred_date = CURDATE() AND preferred_time > CURRENT_TIME()))
                AND preferred_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)
                AND status = 'approved'
                AND is_read = 0  -- Exclude read notifications
                ORDER BY preferred_date ASC
            ");
            $upcomingRequestsStmt->execute(['user_id' => $userId]);
            $upcomingRequests = $upcomingRequestsStmt->fetchAll(PDO::FETCH_ASSOC);


            if (count($upcomingRequests) > 0): 
        ?>
        <div class="notification notification-info">
            <strong>Upcoming Nutritionist Meetings:</strong>
            <ul>
                <?php
                    $counter = 1; // Initialize a counter
                    foreach ($upcomingRequests as $request):
                ?>
                    <li id="request-<?php echo $request['id']; ?>">
                        <?php echo $counter++; ?>.
                        Date: <?php echo htmlspecialchars($request['preferred_date']); ?>, 
                        Time: <?php echo htmlspecialchars($request['preferred_time']); ?>
                        <button onclick="markAsRead(<?php echo $request['id']; ?>)">Mark as Read</button>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php else: ?>
        <div class="notification notification-info">
            <strong>You have no current notifications in your inbox.</strong>
        </div>
    <?php endif; ?>
        </div>


    <div class="sign-up-classes">
    <h1>Sign Up for Our Fitness Classes</h1>
    <div class="card-container">
        <div class="card">
            <h2>Yoga Class</h2>
            <p>Join our relaxing yoga sessions.</p>
            <p>Price: RM50</p>
            <button onclick="selectClass('Yoga', 50)">Sign Up</button>
        </div>
        <div class="card">
            <h2>HIIT Training</h2>
            <p>Intense HIIT workouts to boost your fitness.</p>
            <p>Price: RM75</p>
            <button onclick="selectClass('HIIT', 75)">Sign Up</button>
        </div>
        <div class="card">
            <h2>Weightlifting</h2>
            <p>Build strength with our weightlifting classes.</p>
            <p>Price: RM100</p>
            <button onclick="selectClass('Weightlifting', 100)">Sign Up</button>
        </div>
    </div>

    <!-- Sign Up Form Section -->
    <div id="signupFormSection" style="display: none;">
        <h2>Sign Up for Class</h2>
        <div id="selectedClassMessage" style="display: none;"></div>
        <form id="signupForm" method="POST" action="user_dashboard.php" class="signupforms">
            <input type="hidden" name="classID" id="classID" />
            <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id']; ?>">
            <input type="hidden" name="price" id="price" />
            <input type="hidden" name="paymentMethod" id="paymentMethod" value="" required />

            <label for="first_name">Name:</label>
            <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" readonly />

            <label for="email">Email:</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly />

            <label for="phone">Phone Number:</label>
            <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" readonly />

            <h3>Select Payment Method:</h3>
            <div class="payment-options">
                <button type="button" class="payment-button" data-value="credit_card" onclick="selectPaymentMethod('credit_card')">Credit Card</button>
                <button type="button" class="payment-button" data-value="e_wallet" onclick="selectPaymentMethod('e_wallet')">E-Wallet</button>
                <button type="button" class="payment-button" data-value="bank_transfer" onclick="selectPaymentMethod('bank_transfer')">Bank Transfer</button>
                <button type="button" class="payment-button" data-value="cash" onclick="selectPaymentMethod('cash')">Cash</button>
            </div>

            <div id="additionalFields" style="display: none;">
                <label for="start_date">Start Date</label>
                <input type="date" name="start_date" id="start_date" required onchange="setEndDate()" />

                <label for="end_date">End Date</label>
                <input type="date" name="end_date" id="end_date" readonly required />
            </div>

            <button type="submit" id="submitButton">Submit</button>
        </form>
    </div>
</div>


        <!--Body Data Form-->
        <div class="forms body-form" id="body-data-section">
            <h1>Body Data</h1>
            <form method="POST" action="manage_body_data.php">
                <label for="height">Height (cm):</label>
                <input type="number" name="height" id="height" required oninput="calculateBMI()">

                <label for="weight">Weight (kg):</label>
                <input type="number" name="weight" id="weight" required oninput="calculateBMI()">

                <label for="bmi">BMI:</label>
                <input type="text" name="bmi" id="bmi" readonly>

                <label for="exercise">Exercise (type or duration):</label>
                <input type="text" name="exercise" id="exercise" required>

                <label for="water_consumption">Water Consumption (liters):</label>
                <input type="number" name="water_consumption" id="water_consumption" step="0.01" required>

                <button type="submit" name="submit_body_data">Save</button>
            </form>
        </div>


        <div class="forms nutritionist-form" id="request-nutritionist-section">
    <h1>Request Nutritionist</h1>
    <div class="payment-card">
        <p class="nutritionist-fee">Consultation Fee: RM20 per session</p>
        
        <h3>Select Payment Method:</h3>
        <div class="payment-options">
            <button type="button" class="payment-button" data-value="credit_card">Credit Card</button>
            <button type="button" class="payment-button" data-value="e_wallet">E-Wallet</button>
            <button type="button" class="payment-button" data-value="bank_transfer">Bank Transfer</button>
            <button type="button" class="payment-button" data-value="cash">Cash</button>
        </div>
        
        <button id="request-meeting" type="button">Select Payment Type</button>
        <p id="error-message" style="color: red; display: none;">Please select a payment method before requesting a meeting.</p>
    </div>

    <form id="meeting-form" method="POST" action="request_nutritionist_user.php" style="display: none;">
        <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id']; ?>">
        <input type="hidden" name="payment_method" id="payment-method" value="">
        
        <label for="preferred_date">Preferred Date:</label>
        <input type="date" name="preferred_date" required>
        
        <label for="preferred_time">Preferred Time:</label>
        <input type="time" name="preferred_time" required>
        
        <button type="submit" name="request_meeting">Request Meeting</button>
    </form>
</div>


<div class="request-history" id="request-history-section">
    <h1>Request History</h1>
    <div style="display: flex;">
        <input type="text" id="searchRequestID" class="search-bar" placeholder="Search by Request ID..." onkeyup="searchRequestTable()">
        <button onclick="resetRequestSearch()" class="reset-search">Reset</button>
    </div>

    <?php
    // Fetch requests and payment statuses with pagination
    $sql = "SELECT r.id, r.preferred_date, r.preferred_time, r.payment_method, r.status, p.status AS payment_status
            FROM nutritionist_requests r
            LEFT JOIN payments p ON r.id = p.request_id
            WHERE r.user_id = :user_id
            ORDER BY r.id DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <table>
        <thead>
            <tr>
                <th>Request ID</th>
                <th>Date</th>
                <th>Time</th>
                <th>Payment Method</th>
                <th>Request Status</th>
                <th>Payment Status</th>
            </tr>
        </thead>
        <tbody id="requestTableBody">
            <?php if (!empty($requests)): ?>
                <?php foreach ($requests as $request): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($request['id']); ?></td>
                        <td><?php echo htmlspecialchars($request['preferred_date']); ?></td>
                        <td><?php echo htmlspecialchars($request['preferred_time']); ?></td>
                        <td><?php echo htmlspecialchars($paymentMethodMapping[$request['payment_method']] ?? 'Unknown'); ?></td>
                        <td><?php echo htmlspecialchars($request['status'] ?: 'Pending'); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($request['payment_status'] ?: 'Pending')); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align: center;">No data available.</td>
                </tr>
            <?php endif; ?>
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
    <div class="body-data-history" id="body-data-history-section">
    <h1>Manage Your Body Data</h1>
    <div style="display: flex;">
        <input type="text" id="searchBar" class="search-bar" placeholder="Search by Data ID..." onkeyup="searchTable()">
        <button onclick="resetSearch()" class="reset-search">Reset</button>
    </div>


<table id="bodyDataTable">
    <thead>
        <tr>
            <th>Data ID</th>
            <th>Height (cm)</th>
            <th>Weight (kg)</th>
            <th>BMI</th>
            <th>Exercise</th>
            <th>Water Consumption (liters)</th>
            <th>Date/Time Created</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($bodyDataHistory)): ?>
        <?php foreach ($bodyDataHistory as $data): ?>
            <tr>
                <td><?php echo htmlspecialchars($data['id']); ?></td>
                <td><?php echo htmlspecialchars($data['height']); ?></td>
                <td><?php echo htmlspecialchars($data['weight']); ?></td>
                <td><?php echo htmlspecialchars($data['bmi']); ?></td>
                <td><?php echo htmlspecialchars($data['exercise']); ?></td>
                <td><?php echo htmlspecialchars($data['water_consumption']); ?></td>
                <td><?php echo htmlspecialchars($data['created_at']); ?></td>
                <td>
                    <form action="update_body_data.php" method="POST" style="display:inline;">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($data['id']); ?>">
                        <button type="submit" class="update">Update</button>
                    </form>
                    <form action="delete_body_data.php" method="POST" style="display:inline;">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($data['id']); ?>">
                        <button type="submit" class="delete" onclick="return confirm('Are you sure you want to delete this entry?');">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align: center;">No data available.</td>
                </tr>
            <?php endif; ?>
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
        <button id="back-to-homepage" onclick="goBackToHomepage()" style="margin-top: 10px;">Go Back to Homepage</button>

        <!-- Logout Button -->
        <form method="POST" action="logout.php">
            <button type="submit" class="logout-button">Logout</button>
        </form>
    </div>

    <!-- Edit Profile Form -->
    <div id="edit-profile-form" style="display: none;">
        <form method="POST" action="update_user_profile.php">
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
            <button class="cancel-button" type="button" id="cancel-btn=" onclick="cancelEditProfile()">Cancel</button>
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

     window.onload = function() {
        const message = document.getElementById('flash-message');
        if (update_profile_message) {
            setTimeout(() => {
                message.style.display = 'none';
            }, 3000); // 3000 milliseconds = 3 seconds
        }
    };

   function calculateBMI() {
    const heightInput = document.getElementById('height');
    const weightInput = document.getElementById('weight');
    const bmiInput = document.getElementById('bmi');

    const height = parseFloat(heightInput.value);
    const weight = parseFloat(weightInput.value);

    if (height > 0 && weight > 0) {
        const bmi = weight / ((height / 100) * (height / 100));
        bmiInput.value = bmi.toFixed(2);
    } else {
        bmiInput.value = '';
    }
}


function searchTable() {
    const input = document.getElementById('searchBar');
    const filter = input.value.trim().toLowerCase(); // Trim and convert to lowercase
    const table = document.getElementById('bodyDataTable');
    const rows = table.getElementsByTagName('tr');

    for (let i = 1; i < rows.length; i++) { // Start from 1 to skip the header row
        const cells = rows[i].getElementsByTagName('td');
        const idCell = cells[0]; // Get the ID cell (first column)
        
        // Check if the ID cell includes the filter value
        if (idCell.innerText.trim().toLowerCase().includes(filter)) {
            rows[i].style.display = ''; // Show the row
        } else {
            rows[i].style.display = 'none'; // Hide the row
        }
    }
}

function resetSearch() {
    const input = document.getElementById('searchBar');
    input.value = ''; // Clear the input field
    searchTable(); // Show all rows
}

function searchRequestTable() {
    const input = document.getElementById('searchRequestID');
    const filter = input.value.trim().toLowerCase();
    const table = document.getElementById('requestTableBody');
    const rows = table.getElementsByTagName('tr');

    for (let i = 0; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName('td');
        const requestIDCell = cells[0]; // Get the Request ID cell (first column)

        // Check if the Request ID cell includes the filter value
        if (requestIDCell.innerText.trim().toLowerCase().includes(filter)) {
            rows[i].style.display = ''; // Show the row
        } else {
            rows[i].style.display = 'none'; // Hide the row
        }
    }
}

function resetRequestSearch() {
    const input = document.getElementById('searchRequestID');
    input.value = ''; // Clear the input field
    searchRequestTable(); // Show all rows
}

 document.addEventListener('DOMContentLoaded', function() {
    const buttons = document.querySelectorAll('.payment-button');
    const requestButton = document.getElementById('request-meeting');
    const errorMessage = document.getElementById('error-message');
    const meetingForm = document.getElementById('meeting-form');
    const paymentMethodInput = document.getElementById('payment-method');
    let selectedPayment = '';

    buttons.forEach(button => {
        button.addEventListener('click', () => {
            buttons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            selectedPayment = button.getAttribute('data-value');
            paymentMethodInput.value = selectedPayment; // Set the payment method value
            errorMessage.style.display = 'none'; // Hide error message
        });
    });

        requestButton.addEventListener('click', () => {
        if (!selectedPayment) {
            errorMessage.style.display = 'block'; // Show error message
        } else {
            paymentMethodInput.value = selectedPayment; // Set the payment method value
            meetingForm.style.display = 'block'; // Show the meeting form
        }
    });
});

function resetForm() {
    // Reset the form fields
    document.getElementById("signupForm").reset();
    document.getElementById("additionalFields").style.display = "none";
    document.getElementById("submitButton").style.display = "none";
    const buttons = document.querySelectorAll('.payment-button');
    buttons.forEach(btn => btn.classList.remove('active'));
}

document.addEventListener('DOMContentLoaded', function() {
    const buttons = document.querySelectorAll('.payment-button');
    const additionalFields = document.getElementById('additionalFields');
    const paymentMethodInput = document.getElementById('paymentMethod');
    const submitButton = document.getElementById('submitButton');
    let selectedPayment = '';

    buttons.forEach(button => {
        button.addEventListener('click', () => {
            buttons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            selectedPayment = button.getAttribute('data-value');
            paymentMethodInput.value = selectedPayment; // Set the payment method value
            additionalFields.style.display = 'block'; // Show additional fields
            submitButton.style.display = 'block'; // Show submit button
        });
    });
});

document.getElementById("signupForm").addEventListener("submit", function(event) {
    const telInput = document.querySelector('input[type="tel"]');
    const regex = /^[0-9]+$/; // Only digits

    if (!regex.test(telInput.value)) {
        event.preventDefault(); // Prevent form submission
        alert("Please enter a valid phone number.");
    }
});

    function selectClass(className, price) {
        document.getElementById('classID').value = className;
        document.getElementById('price').value = price;
        document.getElementById('selectedClassMessage').innerText = `You have selected the ${className} class for RM${price}.`;
        document.getElementById('selectedClassMessage').style.display = 'block';
        document.getElementById('signupFormSection').style.display = 'block';
        document.getElementById('additionalFields').style.display = 'block';
    }

    function selectPaymentMethod(method) {
        document.getElementById('paymentMethod').value = method;
    }

    function setEndDate() {
        const startDate = new Date(document.getElementById('start_date').value);
        const endDate = new Date(startDate);
        endDate.setMonth(startDate.getMonth() + 1); // Set end date to one month later
        document.getElementById('end_date').value = endDate.toISOString().split('T')[0];
    }
    </script>
</body>
</html>
