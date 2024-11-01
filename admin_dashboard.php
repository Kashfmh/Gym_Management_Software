<?php
session_start();
include 'database_connection.php';
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}
if (isset($_POST['logout'])) {
    session_destroy(); 
    header('Location: index.php'); 
    exit;
}

// Function to fetch users
function fetchUsers($pdo) {
    return $pdo->query('SELECT id, first_name, last_name FROM users')->fetchAll(PDO::FETCH_ASSOC);
}

// Handle Nutritionist Request Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_meeting'])) {
    $user_id = $_POST['user_id'];
    $preferred_date = $_POST['preferred_date'];
    $preferred_time = $_POST['preferred_time'];
    $payment_method = $_POST['payment_method'];

    
    $stmt = $pdo->prepare("INSERT INTO nutritionist_requests (user_id, preferred_date, preferred_time, payment_method) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $preferred_date, $preferred_time, $payment_method]);

    
    $request_id = $pdo->lastInsertId();

    // Insert payment record
    $amount = 20.00;
    $status = 'Pending';
    $paymentStmt = $pdo->prepare("INSERT INTO payments (request_id, user_id, amount, payment_method, payment_date, status) VALUES (?, ?, ?, ?, ?, ?)");
    $paymentStmt->execute([$request_id, $user_id, $amount, $payment_method, $preferred_date, $status]);

    $_SESSION['success_message'] = "Nutritionist request submitted successfully.";

    header('Location: admin_dashboard.php');
    exit;
}
// Handle Request Actions (Approve/Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'];
    $action = $_POST['action'];

    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE nutritionist_requests SET status = 'approved' WHERE id = ?");
        $stmt->execute([$request_id]);
         $_SESSION['success_message'] = "Request approved successfully.";
    } elseif ($action === 'reject') {
         $_SESSION['error_message'] = "Request rejected.";
    }
}

// Pagination Variables
$limit = 5; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; 
$offset = ($page - 1) * $limit; 
$users = fetchUsers($pdo);

// Fetch nutritionist requests
$filterUserId = isset($_POST['user_id']) ? $_POST['user_id'] : null;
$requestQuery = 'SELECT nr.*, u.id AS user_id, u.first_name, u.last_name 
                 FROM nutritionist_requests nr
                 JOIN users u ON nr.user_id = u.id';
if ($filterUserId) {
    $requestQuery .= ' WHERE nr.user_id = :user_id';
}
$requestQuery .= ' ORDER BY nr.created_at DESC LIMIT :limit OFFSET :offset';

$requestStmt = $pdo->prepare($requestQuery);
if ($filterUserId) {
    $requestStmt->bindParam(':user_id', $filterUserId, PDO::PARAM_INT);
}
$requestStmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$requestStmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$requestStmt->execute();
$requests = $requestStmt->fetchAll(PDO::FETCH_ASSOC);

// Count total requests for pagination
$totalRequests = $pdo->query('SELECT COUNT(*) FROM nutritionist_requests')->fetchColumn();
$totalPages = ceil($totalRequests / $limit); 

// Fetch admin data
$admin_id = $_SESSION['admin_id'];
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    echo "Admin not found.";
    exit;
}

// Fetch payments with pagination and search
$search = isset($_GET['search']) ? $_GET['search'] : '';
$total_records_query = $pdo->prepare('SELECT COUNT(*) FROM payments p JOIN users u ON p.user_id = u.id 
                                       WHERE u.first_name LIKE :search OR u.last_name LIKE :search OR p.user_id LIKE :search');
$search_param = '%' . $search . '%';
$total_records_query->bindParam(':search', $search_param);
$total_records_query->execute();
$total_records = $total_records_query->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch records for current page with search
$paymentStmt = $pdo->prepare('SELECT p.id, p.request_id, p.amount, p.payment_method, p.payment_date, p.status, u.id AS user_id, u.first_name, u.last_name 
                                FROM payments p
                                JOIN nutritionist_requests nr ON p.request_id = nr.id
                                JOIN users u ON nr.user_id = u.id
                                WHERE (u.first_name LIKE :search OR u.last_name LIKE :search OR nr.user_id LIKE :search)
                                AND nr.status = "approved"
                                ORDER BY p.id DESC
                                LIMIT :start_from, :records_per_page');

$paymentStmt->bindParam(':search', $search_param);
$paymentStmt->bindParam(':start_from', $offset, PDO::PARAM_INT);
$paymentStmt->bindParam(':records_per_page', $limit, PDO::PARAM_INT);
$paymentStmt->execute();
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

        <!-- Display success message -->
        <?php if (isset($_SESSION['success_message'])): ?>
        <div class="success-message" id="success-message">
            <?php echo htmlspecialchars($_SESSION['success_message']); ?>
        </div>
        <?php unset($_SESSION['success_message']); endif; ?>

        <!-- Display error message -->
        <?php if (isset($_SESSION['error_message'])): ?>
        <div class="error-message" id="error-message">
            <?php echo htmlspecialchars($_SESSION['error_message']); ?>
        </div>
        <?php unset($_SESSION['error_message']); endif; ?>
        
        <div class="content" id="content-hide">
            <div class="table-forms" id="nutritionist-request-section">
                <h1>Request Actions</h1>
                <div class="search-bar">
                    <input type="text" id="requestSearchBar" placeholder="Search by ID or Name..." onkeyup="searchRequestTable()" />
                    <button class="reset-button" type="button" onclick="resetSearchRequests()">Reset</button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Request ID</th>
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
                    <tbody id="paymentManagementTable">
                        <?php if (!empty($requests)): ?>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($request['id']); ?></td>
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

            <!-- Admin Nutrition Request -->
            <div class="form-request" id="request-nutritionist-section">
                <form method="POST" action="">
                    <h1>Nutrition Request Form</h1>
                    <label for="user_id">Select User:</label>
                    <select class="request-form-select" name="user_id" required>
                        <option value="">--Select a User--</option>
                        <?php foreach ($users as $user): ?>
                            <option value='<?php echo $user['id']; ?>'><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></option>
                        <?php endforeach; ?>
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
                <div class="search-bar">
                    <input type="text" id="searchBar" placeholder="Search by ID or Name..." onkeyup="searchTable()" />
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
                            $paymentDate = new DateTime($payment['payment_date']);
                            $formattedDate = $paymentDate->format('d-m-Y'); 
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($payment['id']); ?></td>
                                <td><?php echo htmlspecialchars($payment['user_id']); ?></td>
                                <td><?php echo htmlspecialchars($payment['first_name']); ?></td>
                                <td><?php echo htmlspecialchars($payment['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($payment['amount']); ?></td>
                                <td><?php echo htmlspecialchars($paymentMethodMapping[$payment['payment_method']] ?? 'Unknown'); ?></td>
                                <td><?php echo htmlspecialchars($formattedDate); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($payment['status'] ?: 'Pending')); ?></td>
                                <td>
                                    <form method="POST" action="manage_payments.php">
                                        <input type='hidden' name='payment_id' value='<?php echo $payment['id']; ?>'>
                                        <button type='submit' name='action' value='mark_completed' id="completed">Completed</button>
                                        <button type='submit' name='action' value='mark_failed' id="failed">Failed</button>
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
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>">« Previous</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="<?php if ($i == $page) echo 'active'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>">Next »</a>
                    <?php endif; ?>
                </div>
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

            <button id="back-to-homepage" onclick="goBackToHomepage()" style="margin-top: 10px;">Go Back to Homepage</button>

            <form method="POST" action="">
                <button id="logout-admin" type="submit" class="logout-button" name="logout">Logout</button>
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
        event.preventDefault(); 

        // Create a hidden input to specify the action
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'manage_requests.php';

        const requestIdInput = document.createElement('input');
        requestIdInput.type = 'hidden';
        requestIdInput.name = 'request_id';
        requestIdInput.value = requestId;

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = action;

        form.appendChild(requestIdInput);
        form.appendChild(actionInput);
        document.body.appendChild(form); 

        form.submit(); 
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
            row.style.display = ''; 
        } else {
            row.style.display = 'none'; 
        }
    });
}

function resetSearch() {
    const input = document.getElementById('searchBar');
    input.value = ''; 
    searchTable(); 
}

function resetSearchRequests() {
    const input = document.getElementById('requestSearchBar');
    input.value = ''; 
    searchRequestTable(); 
}

function sortTableByDate() {
    const table = document.getElementById('bodyDataTable');
    const rows = Array.from(table.getElementsByTagName('tr'));

    
    rows.sort((a, b) => {
        const dateA = new Date(a.cells[6].innerText); 
        const dateB = new Date(b.cells[6].innerText);
        return dateB - dateA; 
    });

    
    rows.forEach(row => table.appendChild(row));
}


function searchRequestTable() {
    const input = document.getElementById('requestSearchBar');
    const filter = input.value.toLowerCase();
    const rows = document.querySelectorAll('#paymentManagementTable tr');

    rows.forEach(row => {
        const cells = row.getElementsByTagName('td');
        const userId = cells[0].textContent.toLowerCase();
        const firstName = cells[1].textContent.toLowerCase();
        const lastName = cells[2].textContent.toLowerCase();

        if (userId.includes(filter) || firstName.includes(filter) || lastName.includes(filter)) {
            row.style.display = ''; 
        } else {
            row.style.display = 'none'; 
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {

    function fadeOut(element) {
        element.style.opacity = '1';
        setTimeout(() => {
            element.style.opacity = '0';
            setTimeout(() => {
                element.remove();
            }, 500);
        }, 3000);
    }

    // Check for success and error messages
    const successMessage = document.getElementById('success-message');
    const errorMessage = document.getElementById('error-message');

    if (successMessage) {
        fadeOut(successMessage);
    }

    if (errorMessage) {
        fadeOut(errorMessage);
    }
});
    </script>
</body>
</html>
