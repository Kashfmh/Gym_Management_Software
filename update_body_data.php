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

// Check if ID is set
if (isset($_POST['id'])) {
    $id = $_POST['id'];

    // Fetch current body data
    $stmt = $pdo->prepare("SELECT * FROM body_data_history WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        $_SESSION['error_message'] = "Data not found.";
        header('Location: user_dashboard.php');
        exit;
    }

    // Handle form submission for updating
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_body_data'])) {
        $height = $_POST['height'];
        $weight = $_POST['weight'];
        $bmi = $_POST['bmi'];
        $exercise = $_POST['exercise'];
        $water_consumption = $_POST['water_consumption'];

        // Update body data
        $updateStmt = $pdo->prepare("UPDATE body_data_history SET height = ?, weight = ?, bmi = ?, exercise = ?, water_consumption = ? WHERE id = ?");
        if ($updateStmt->execute([$height, $weight, $bmi, $exercise, $water_consumption, $id])) {
            $_SESSION['success_message'] = "Body data successfully updated.";
        } else {
            $_SESSION['error_message'] = "Failed to update body data.";
        }

        // Redirect back to user dashboard
        header('Location: user_dashboard.php');
        exit;
    }
} else {
    $_SESSION['error_message'] = "Invalid request.";
    header('Location: user_dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Body Data</title>
    <link rel="stylesheet" href="styles/update_body_data.css">
</head>
<body>
    <h1>Update Body Data</h1>
<form method="POST" action="update_body_data.php">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($data['id']); ?>">
    
    <label for="height">Height (cm):</label>
    <input type="number" id="height" name="height" value="<?php echo htmlspecialchars($data['height']); ?>" oninput="calculateBMI()" required>

    <label for="weight">Weight (kg):</label>
    <input type="number" id="weight" name="weight" value="<?php echo htmlspecialchars($data['weight']); ?>" oninput="calculateBMI()" required>

    <label for="bmi">BMI:</label>
    <input type="text" id="bmi" name="bmi" value="<?php echo htmlspecialchars($data['bmi']); ?>" readonly>

    <label for="exercise">Exercise (type or duration):</label>
    <input type="text" name="exercise" id="exercise" value="<?php echo htmlspecialchars($data['exercise']); ?>" required>

    <label for="water_consumption">Water Consumption (liters):</label>
    <input type="number" name="water_consumption" id="water_consumption" value="<?php echo htmlspecialchars($data['water_consumption']); ?>" step="0.01" required>

    <button type="submit" name="update_body_data">Update</button>
    <button type="button" onclick="window.location.href='user_dashboard.php';" style="margin-top: 10px; background-color: #dc3545;">Cancel</button>
</form>

<script>
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
</script>

</body>
</html>
