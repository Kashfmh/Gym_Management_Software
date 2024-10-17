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


// Pagination Variables
$limit = 10; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page number
$offset = ($page - 1) * $limit; // Offset for SQL query

// Fetch all users for the dropdown
$userStmt = $pdo->query('SELECT id, first_name, last_name FROM users');
$users = $userStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch nutritionist requests
$filterUserId = isset($_POST['user_id']) ? $_POST['user_id'] : null;

$requestQuery = 'SELECT nr.*, u.id AS user_id, u.first_name, u.last_name 
                 FROM nutritionist_requests nr
                 JOIN users u ON nr.user_id = u.id';
if ($filterUserId) {
    $requestQuery .= ' WHERE nr.user_id = :user_id';
}
$requestQuery .= ' ORDER BY nr.created_at DESC LIMIT :limit OFFSET :offset'; // Order by created_at


$requestStmt = $pdo->prepare($requestQuery);
if ($filterUserId) {
    $requestStmt->bindParam(':user_id', $filterUserId, PDO::PARAM_INT);
}
$requestStmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$requestStmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$requestStmt->execute();
$requests = $requestStmt->fetchAll(PDO::FETCH_ASSOC);

// Count total requests for pagination
$countQuery = 'SELECT COUNT(*) FROM nutritionist_requests';
$totalRequests = $pdo->query($countQuery)->fetchColumn();
$totalPages = ceil($totalRequests / $limit); // Total number of pages

$admin_id = $_SESSION['admin_id'];

// Fetch admin data from the database
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    echo "Admin not found.";
    exit;
}
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
            <img src="images/barbell-7834321_640-removebg-preview.png" width="100px" alt="Huan Fitness Centre logo" />
            <a href="#home"><span class="mobile">HUAN FITNESS PALS (ADMIN PAGE)</span></a>
        </div>
         <div class="middle-section">
            <div class="extra">
                <div><a href="#nutritionist-request-section">Request Actions</a></div>
                <div><a href="#request-nutritionist-section">Request Nutritionist</a></div>
                <div><a href="#body-data-history-section">Body Data History</a></div>
                
            </div>
        </div>
        <div class="right-section">
            <div class="user-profile">
                <button class="profile-btn-admin">
                    <img src="images/white_account.png" height="40px"/>
                </button>
            </div>
        </div>
    </div>

    <div id="main-content" class="main">
        <div class="welcome-message" id="dashboard-section">
            <h1>Welcome, Admin!</h1>
        </div>
        
        <div class="content" id="content-hide">
        <div class="table-forms" id="nutritionist-request-section">
            <h1>Request Actions</h1>
            <form method="POST" action="" class="select-user-table">
            <label for="user_id"><span id="select-user">Select User:</span></label>
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
    <thead>
        <tr>
            <th>User ID</th>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Date</th>
            <th>Time</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($requests as $request): ?>
            <tr>
                <td><?php echo htmlspecialchars($request['user_id']); ?></td>
                <td><?php echo htmlspecialchars($request['first_name']); ?></td>
                <td><?php echo htmlspecialchars($request['last_name']); ?></td>
                <td><?php echo htmlspecialchars($request['preferred_date']); ?></td>
                <td><?php echo htmlspecialchars($request['preferred_time']); ?></td>
                <td><?php echo htmlspecialchars($request['status']); ?></td>
                <td>
                    <form method="POST" action="manage_requests.php" onsubmit="return handleFormSubmit(event, '<?php echo $request['id']; ?>', 'approve')">
                        <input type='hidden' name='request_id' value='<?php echo $request['id']; ?>'>
                        <button type='submit'>Approve</button>
                    </form>
                    <form method="POST" action="manage_requests.php" onsubmit="return handleFormSubmit(event, '<?php echo $request['id']; ?>', 'reject')">
                        <input type='hidden' name='request_id' value='<?php echo $request['id']; ?>'>
                        <button type='submit'>Reject</button>
                    </form>
                </td>
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

                    <!--Admin Nutrition Request-->
    <div class="form-request" id="request-nutritionist-section">
    <form method="POST" action="request_nutritionist.php"> <!-- Ensure action points to the correct file -->
        <h1>Nutrition Request Form</h1>
        <label for="user_id" id="nut">Select User:</label>
        <select class="request-form-select" name="user_id" required>
            <?php
            // Fetch users from the database
            $stmt = $pdo->query('SELECT id, first_name, last_name FROM users');
            while ($user = $stmt->fetch()) {
                echo "<option value='{$user['id']}'>{$user['first_name']} {$user['last_name']}</option>";
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
            <img src="images/account_circle_24dp_434343_FILL0_wght400_GRAD0_opsz24.png" alt="Admin Avatar" />
            <h2 id="admin-name"><?php echo htmlspecialchars($admin['name']); ?></h2>
            <p><?php echo htmlspecialchars($admin['email']); ?></p>
            <button onclick="editProfile()">Edit</button>
        </div>
        <div class="profile-details">
            <p><strong>Name:</strong> <span id="name"><?php echo htmlspecialchars($admin['name']); ?></span></p>
            <p><strong>Email:</strong> <span id="email"><?php echo htmlspecialchars($admin['email']); ?></span></p>
            <p><strong>Mobile:</strong> <span id="mobile"><?php echo htmlspecialchars($admin['mobile']); ?></span></p>
        </div>

        <!-- Go Back to Homepage Button -->
    <button id="back-to-homepage" onclick="goBackToHomepage()" style="margin-top: 10px;">Go Back to Homepage</button>

        <!-- Logout Button -->
        <form method="POST" action="logout.php">
            <button id="logout-admin" type="submit" class="logout-button">Logout</button>
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
    const profileBtn = document.querySelector('.profile-btn-admin'); // Updated class name
    const profileSection = document.getElementById('profile-section');
    const mainContent = document.getElementById('main-content');
    const editProfileForm = document.getElementById('edit-profile-form');

    if (profileBtn) {
        profileBtn.addEventListener('click', function() {
            profileSection.style.display = 'block';
            mainContent.style.display = 'none';
        });
    }

    window.editProfile = function() {
        editProfileForm.style.display = 'block';
        profileSection.style.display = 'none';
    };

    window.cancelEdit = function() {
        editProfileForm.style.display = 'none';
        profileSection.style.display = 'block';
    };

    window.goBackToHomepage = function() {
        profileSection.style.display = 'none';
        mainContent.style.display = 'block';
    };
});

function handleFormSubmit(event, requestId, action) {
    event.preventDefault(); // Prevent the default form submission

    // Store the current scroll position
    const scrollPosition = window.scrollY;

    // Create a hidden input to specify the action
    const formData = new FormData();
    formData.append('request_id', requestId);
    formData.append('action', action);

    // Create a new XMLHttpRequest
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'manage_requests.php', true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            // Handle success response
            alert(xhr.responseText); // You can customize this
            // Optionally, you can refresh the request list here
            location.reload(); // Reload to see changes
        } else {
            // Handle error response
            alert('Error: ' + xhr.statusText);
        }
    };
    xhr.send(formData);

    // Restore the scroll position after a slight delay
    setTimeout(() => {
        window.scrollTo(0, scrollPosition);
    }, 100);
}

</script>
</body>
</html>
