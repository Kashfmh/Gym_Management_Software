<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_logged_in']) || !isset($_SESSION['user_id'])) {
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
    die("Could not connect to the database $db :" . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_body_data'])) {
    $user_id = $_SESSION['user_id'];
    
    // Validate and sanitize input data
    $height = filter_input(INPUT_POST, 'height', FILTER_VALIDATE_FLOAT);
    $weight = filter_input(INPUT_POST, 'weight', FILTER_VALIDATE_FLOAT);
    $bmi = filter_input(INPUT_POST, 'bmi', FILTER_VALIDATE_FLOAT);
    $exercise = filter_input(INPUT_POST, 'exercise', FILTER_SANITIZE_STRING);
    $water_consumption = filter_input(INPUT_POST, 'water_consumption', FILTER_VALIDATE_FLOAT);
    $date = date('Y-m-d H:i:s'); // Capture the current date and time

    // Check if the inputs are valid
    if ($height === false || $weight === false || $bmi === false || empty($exercise) || $water_consumption === false) {
        header('Location: manage_body_data.php?error=Invalid input');
        exit;
    }

    // Prepare and execute the insert statement
    try {
        $stmt = $pdo->prepare('INSERT INTO body_data_history (user_id, height, weight, bmi, exercise, water_consumption, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$user_id, $height, $weight, $bmi, $exercise, $water_consumption, $date]);

        // Set a success message in the session
        $_SESSION['success_message'] = "Body data successfully added.";

        // Redirect after saving data
        header('Location: user_dashboard.php');
        exit;
    } catch (PDOException $e) {
        die("Could not save data: " . $e->getMessage());
    }
}
?>
