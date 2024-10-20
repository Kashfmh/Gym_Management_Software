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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_meeting'])) {
    // Get form data
    $user_id = $_POST['user_id'];
    $preferred_date = $_POST['preferred_date'];
    $preferred_time = $_POST['preferred_time'];
    $payment_method = $_POST['payment_method'];

    // Insert into nutritionist_requests table
    $stmt = $pdo->prepare("INSERT INTO nutritionist_requests (user_id, preferred_date, preferred_time, payment_method) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $preferred_date, $preferred_time, $payment_method]);

    // Insert payment record
    $amount = 20.00; // Fixed amount for each session
    $paymentStmt = $pdo->prepare("INSERT INTO payments (user_id, amount, payment_method, payment_date, status) VALUES (?, ?, ?, ?, ?)");
    $paymentStmt->execute([$user_id, $amount, $payment_method, $preferred_date, $status]);
    
    // Redirect to the same page to avoid resubmission
    header('Location: admin_dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'];
    $action = $_POST['action'];

    if ($action === 'approve') {
        // Update the request status to 'approved'
        $stmt = $pdo->prepare("UPDATE nutritionist_requests SET status = 'approved' WHERE id = ?");
        $stmt->execute([$request_id]);

        echo "Request approved successfully.";
    } elseif ($action === 'reject') {
        // Handle rejection logic
        echo "Request rejected.";
    }
}

// Pagination Variables
$limit = 5; // Number of records per page
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


// Define the number of records per page
$records_per_page = 10;

// Get the current page from the URL, if not set default to 1
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Calculate the starting record
$start_from = ($current_page - 1) * $records_per_page;

// Fetch total number of records
$total_records_query = $pdo->query('SELECT COUNT(*) FROM payments');
$total_records = $total_records_query->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);

// Fetch records for the current page
$paymentStmt = $pdo->prepare('SELECT p.id, p.user_id, p.amount, p.payment_method, p.payment_date, p.status, u.first_name, u.last_name 
                               FROM payments p
                               JOIN users u ON p.user_id = u.id
                               ORDER BY p.payment_date DESC
                               LIMIT :start_from, :records_per_page');
$paymentStmt->bindParam(':start_from', $start_from, PDO::PARAM_INT);
$paymentStmt->bindParam(':records_per_page', $records_per_page, PDO::PARAM_INT);
$paymentStmt->execute();
$payments = $paymentStmt->fetchAll(PDO::FETCH_ASSOC);



// Define the number of records per page
$records_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start_from = ($current_page - 1) * $records_per_page;

// Get the search term from the URL
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Fetch total number of records with search
$total_records_query = $pdo->prepare('SELECT COUNT(*) FROM payments p JOIN users u ON p.user_id = u.id 
                                       WHERE u.first_name LIKE :search OR u.last_name LIKE :search OR p.user_id LIKE :search');
$search_param = '%' . $search . '%';
$total_records_query->bindParam(':search', $search_param);
$total_records_query->execute();
$total_records = $total_records_query->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);

// Fetch records for the current page with search
$paymentStmt = $pdo->prepare('SELECT p.id, p.user_id, p.amount, p.payment_method, p.payment_date, p.status, u.first_name, u.last_name 
                               FROM payments p
                               JOIN users u ON p.user_id = u.id
                               WHERE u.first_name LIKE :search OR u.last_name LIKE :search OR p.user_id LIKE :search
                               ORDER BY p.payment_date DESC
                               LIMIT :start_from, :records_per_page');
$paymentStmt->bindParam(':search', $search_param);
$paymentStmt->bindParam(':start_from', $start_from, PDO::PARAM_INT);
$paymentStmt->bindParam(':records_per_page', $records_per_page, PDO::PARAM_INT);
$paymentStmt->execute();
$payments = $paymentStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch payments for approved requests
$paymentQuery = "
    SELECT p.*, nr.id AS request_id, u.first_name, u.last_name 
    FROM payments p
    JOIN nutritionist_requests nr ON p.request_id = nr.id
    JOIN users u ON p.user_id = u.id
    WHERE nr.status = 'Approved'
    ORDER BY p.payment_date DESC
";
$paymentStmt = $pdo->query($paymentQuery);
$payments = $paymentStmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="styles/admin_dashboard.css" />
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
            <h1>Welcome, <?php echo htmlspecialchars($admin['name']); ?>!</h1>
        </div>
        
        <div class="content" id="content-hide">
            <div class="table-forms" id="nutritionist-request-section">
    <h1>Request Actions</h1>
    <div class="search-bar">
        <input type="text" id="requestSearchBar" placeholder="Search by User ID or Name..." onkeyup="searchRequestTable()" />
        <button class="reset-button" type="button" onclick="resetSearchRequests()">Reset</button>
    </div>
    <table>
        <thead>
            <tr>
                <th>User ID</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Date</th>
                <th>Time</th>
                <th>Payment Method</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody id="requestBodyDataTable">
            <?php if (!empty($requests)): ?>
            <?php foreach ($requests as $request): ?>
                <tr>
                    <td><?php echo htmlspecialchars($request['user_id']); ?></td>
                    <td><?php echo htmlspecialchars($request['first_name']); ?></td>
                    <td><?php echo htmlspecialchars($request['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($request['preferred_date']); ?></td>
                    <td><?php echo htmlspecialchars($request['preferred_time']); ?></td>
                    <td><?php echo htmlspecialchars($paymentMethodMapping[$request['payment_method']] ?? 'Unknown'); ?></td>
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
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align: center;">No data available.</td>
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
        </div>

        <!-- Admin Nutrition Request -->
        <div class="form-request" id="request-nutritionist-section">
            <form method="POST" action="request_nutritionist_admin.php">
                <h1>Nutrition Request Form</h1>
                <label for="user_id">Select User:</label>
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

                <label for="payment_method">Select Payment Method:</label>
                <select class="payment_method" name="payment_method" required>
                    <option value="">--Select Payment Method--</option>
                    <option value="credit_card">Credit Card</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="cash">Cash</option>
                </select>
                <div>
                <button id="Nutrireq" type="submit" name="request_meeting">Request Meeting</button>
                </div>
            </form>
        </div>

        <!-- Payment Management Section -->
<div class="payment-management" id="payment-management-section">
    <h1>Payment Management</h1>
    <!-- Search Bar -->
    <div class="search-bar">
        <input type="text" id="searchBar" placeholder="Search by Payment ID or Name..." onkeyup="searchTable()" />
        <button class="reset-button" type="button" onclick="resetSearch()">Reset</button>
    </div>
    <table>
        <thead>
            <tr>
                <th>Payment ID</th>
                <th>User ID</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Amount</th>
                <th>Payment Method</th>
                <th>Payment Date</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody id="bodyDataTable">
            <?php if (!empty($payments)): ?>
            <?php foreach ($payments as $payment): 
                // Format the payment date
                $paymentDate = new DateTime($payment['payment_date']);
                $formattedDate = $paymentDate->format('d-m-Y'); // Change format to DD-MM-YYYY
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($payment['id']); ?></td>
                    <td><?php echo htmlspecialchars($payment['user_id']); ?></td>
                    <td><?php echo htmlspecialchars($payment['first_name']); ?></td>
                    <td><?php echo htmlspecialchars($payment['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($payment['amount']); ?></td>
                    <td><?php echo htmlspecialchars($paymentMethodMapping[$payment['payment_method']] ?? 'Unknown'); ?></td>
                    <td><?php echo htmlspecialchars($formattedDate); ?></td> <!-- Display formatted date -->
                    <td><?php echo htmlspecialchars(ucfirst($payment['status'] ?: 'Pending')); ?></td>
                    <td>
                        <form method="POST" action="manage_payments.php">
                            <input type='hidden' name='payment_id' value='<?php echo $payment['id']; ?>'>
                            <button type='submit' name='action' value='mark_completed' id="completed">Mark as Completed</button>
                            <button type='submit' name='action' value='mark_failed' id="failed">Mark as Failed</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" style="text-align: center;">No data available.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <!-- Pagination Links -->
    <div class="pagination">
        <?php if ($current_page > 1): ?>
            <a href="?page=<?php echo $current_page - 1; ?>">« Previous</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?>" class="<?php if ($i == $current_page) echo 'active'; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>

        <?php if ($current_page < $total_pages): ?>
            <a href="?page=<?php echo $current_page + 1; ?>">Next »</a>
        <?php endif; ?>
    </div>
</div>

    </div> <!--End of main-->

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
        <form method="POST" action="update_admin_profile.php">
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
                
                location.reload(); // Reload to see changes
            } else {
                // Handle error response
                alert('Error unable to complete action');
            }
        };
        xhr.send(formData);

        // Restore the scroll position after a slight delay
        setTimeout(() => {
            window.scrollTo(0, scrollPosition);
        }, 100);
    }

    function searchTable() {
    const input = document.getElementById('searchBar');
    const filter = input.value.toLowerCase();
    const rows = document.querySelectorAll('#bodyDataTable tr');

    rows.forEach(row => {
        const cells = row.getElementsByTagName('td');
        const id = cells[0].textContent.toLowerCase();
        const firstName = cells[2].textContent.toLowerCase();
        const lastName = cells[3].textContent.toLowerCase();

        if (id.includes(filter) || firstName.includes(filter) || lastName.includes(filter)) {
            row.style.display = ''; // Show row
        } else {
            row.style.display = 'none'; // Hide row
        }
    });
}

function resetSearch() {
    const input = document.getElementById('searchBar');
    input.value = ''; // Clear the input field
    searchTable(); // Show all rows
}

function resetSearchRequests() {
    const input = document.getElementById('requestSearchBar');
    input.value = ''; // Clear the input field
    searchRequestTable(); // Show all rows
}

function sortTableByDate() {
    const table = document.getElementById('bodyDataTable');
    const rows = Array.from(table.getElementsByTagName('tr'));

    // Sort rows based on payment date (7th column, index 6)
    rows.sort((a, b) => {
        const dateA = new Date(a.cells[6].innerText); // Payment Date
        const dateB = new Date(b.cells[6].innerText);
        return dateB - dateA; // Newest first
    });

    // Append sorted rows back to the table body
    rows.forEach(row => table.appendChild(row));
}

// Call this function when the page loads or after data is added
window.onload = function() {
    sortTableByDate();
};

function searchRequestTable() {
    const input = document.getElementById('requestSearchBar');
    const filter = input.value.toLowerCase();
    const rows = document.querySelectorAll('#requestBodyDataTable tr');

    rows.forEach(row => {
        const cells = row.getElementsByTagName('td');
        const userId = cells[0].textContent.toLowerCase();
        const firstName = cells[1].textContent.toLowerCase();
        const lastName = cells[2].textContent.toLowerCase();

        if (userId.includes(filter) || firstName.includes(filter) || lastName.includes(filter)) {
            row.style.display = ''; // Show row
        } else {
            row.style.display = 'none'; // Hide row
        }
    });
}
    </script>
</body>
</html>
