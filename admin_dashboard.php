<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$host = 'localhost';
$db = 'gym_management';
$user = 'root';        
$pass = '';            

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database: " . $e->getMessage());
}

$admin_id = $_SESSION['admin_id'];

// Fetch admin data from the database
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    echo "Admin not found.";
    exit;
}

// Fetch all users for the dropdown
$userStmt = $pdo->query('SELECT id, first_name, last_name FROM users');
$users = $userStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch nutritionist requests
$filterUserId = isset($_POST['user_id']) ? $_POST['user_id'] : null;
$requestQuery = 'SELECT nr.*, u.first_name, u.last_name 
                 FROM nutritionist_requests nr
                 JOIN users u ON nr.user_id = u.id';
if ($filterUserId) {
    $requestQuery .= ' WHERE nr.user_id = :user_id';
}
$requestStmt = $pdo->prepare($requestQuery);
if ($filterUserId) {
    $requestStmt->execute(['user_id' => $filterUserId]);
} else {
    $requestStmt->execute();
}
$requests = $requestStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="admin_dashboard.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Protest+Strike&display=swap" rel="stylesheet" />
</head>
<body>
    <div class="header">
        <div class="left-section">
            <div class="hamburger">
                <img src="images/menu_24dp_E8EAED_FILL0_wght400_GRAD0_opsz24.png" width="40px" style="margin-right: 20px;"/>
            </div>
            <img src="images/barbell-7834321_640-removebg-preview.png" width="100px" alt="Huan Fitness Centre logo" />
            <a href="#home"> HUAN FITNESS PALS (ADMIN PAGE)</a>
        </div>
        <div class="right-section">
            <div class="user-profile">
                <button class="profile-btn">
                    <img src="images/account_circle_24dp_E8EAED_FILL0_wght400_GRAD0_opsz24.png" height="40px"/>
                </button>
            </div>
        </div>
    </div>

    <div class="left-nav">
        <a href="#dashboard" id="nav-dashboard">Dashboard</a>
        <a href="#Nutrireq" id="nav-nutritionist-request">Nutritionist Request</a>
        <a href="#settings">Settings</a>
        <a href="#logout">Logout</a>
    </div>

    <div id="main-content" class="main">
        <div class="welcome-message" id="dashboard-section">
            <h1>Welcome, Admin!</h1>
        </div>
        
        <div class="content" id="content-hide">
          <form method="POST" action="">
            <label for="user_id">Select User:</label>
            <select name="user_id" id="user_id" onchange="this.form.submit()">
                <option value="">All Users</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?php echo $user['id']; ?>" <?php echo ($filterUserId == $user['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
            <table>
                <tr>
                    <th>User ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Preferred Date</th>
                    <th>Preferred Time</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($requests as $request): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($request['user_id']); ?></td>
                        <td><?php echo htmlspecialchars($request['first_name']); ?></td>
                        <td><?php echo htmlspecialchars($request['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($request['preferred_date']); ?></td>
                        <td><?php echo htmlspecialchars($request['preferred_time']); ?></td>
                        <td><?php echo htmlspecialchars($request['status']); ?></td>
                        <td>
                            <form method='POST' action='manage_requests.php'>
                                <input type='hidden' name='request_id' value='<?php echo $request['id']; ?>'>
                                <button type='submit' name='approve'>Approve</button>
                                <button type='submit' name='reject'>Reject</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
        
        <!--Admin Nutrition Request-->
    <div class="form-request">
    <form method="POST" action="request_nutritionist.php">
      <h1>Nutrition Request Form</h1> <br> 
    <label for="user_id">Select User:</label>
    <select name="user_id" required>
        <?php
        // Assuming $pdo is your PDO connection
        try {
            $stmt = $pdo->query('SELECT id, first_name, last_name FROM users');
            while ($user = $stmt->fetch()) {
                echo "<option value='{$user['id']}'>{$user['first_name']} {$user['last_name']}</option>";
            }
        } catch (PDOException $e) {
            echo "Error fetching users: " . $e->getMessage();
        }
        ?>
    </select>

    <label for="preferred_date">Preferred Date:</label>
    <input type="date" name="preferred_date" required>

    <label for="preferred_time">Preferred Time:</label>
    <input type="time" name="preferred_time" required>

    <button id="Nutrireq" type="submit" name="request_meeting">Request Meeting</button>
</form>
</div>
    </div>

    <!-- Admin Profile Section -->
    <div id="profile-section" style="display: none;">
        <div class="profile-header">
            <img src="images/account_circle_24dp_E8EAED_FILL0_wght400_GRAD0_opsz24.png" alt="Admin Avatar" />
            <h2 id="admin-name"><?php echo htmlspecialchars($admin['name']); ?></h2>
            <p><?php echo htmlspecialchars($admin['email']); ?></p>
            <button onclick="editProfile()">Edit</button>
        </div>
        <div class="profile-details">
            <p><strong>Name:</strong> <span id="name"><?php echo htmlspecialchars($admin['name']); ?></span></p>
            <p><strong>Email:</strong> <span id="email"><?php echo htmlspecialchars($admin['email']); ?></span></p>
            <p><strong>Mobile:</strong> <span id="mobile"><?php echo htmlspecialchars($admin['mobile']); ?></span></p>
        </div>

        <!-- Logout Button -->
        <form method="POST" action="logout.php">
            <button type="submit" class="logout-button">Logout</button>
        </form>
    </div>

    <!-- Edit Profile Form -->
    <div id="edit-profile-form" style="display: none;">
        <h2>Edit Profile</h2>
        <form method="POST" action="update_profile.php">
            <label for="edit-name">Name:</label>
            <input type="text" id="edit-name" name="name" value="<?php echo htmlspecialchars($admin['name']); ?>" required>
            <label for="edit-email">Email:</label>
            <input type="email" id="edit-email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
            <label for="edit-mobile">Mobile:</label>
            <input type="text" id="edit-mobile" name="mobile" value="<?php echo htmlspecialchars($admin['mobile']); ?>" required>
            <button type="submit" id="edit-btn">Save Changes</button>
            <button type="button" onclick="cancelEdit()">Cancel</button>
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

            window.cancelEdit = function() {
                editProfileForm.style.display = 'none';
                profileSection.style.display = 'block';
            };

            // Navigation logic
            const dashboardSection = document.getElementById('dashboard-section');
            const nutritionistRequestSection = document.getElementById('nutritionist-request-section');
            const requestFormSection = document.getElementById('content-hide');

            document.getElementById('nav-dashboard').addEventListener('click', function(event) {
                event.preventDefault();
                dashboardSection.style.display = 'block';
                nutritionistRequestSection.style.display = 'none';
            });

            document.getElementById('nav-nutritionist-request').addEventListener('click', function(event) {
                event.preventDefault();
                dashboardSection.style.display = 'none';
                nutritionistRequestSection.style.display = 'none';
                nutritionistRequestSection.style.display = 'block';
            });
        });
    </script>
</body>
</html>
