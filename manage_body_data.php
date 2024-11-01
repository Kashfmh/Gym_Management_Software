<?php
session_start();
include 'database_connection.php';


if (!isset($_SESSION['user_logged_in']) || !isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_body_data'])) {
    $user_id = $_SESSION['user_id'];
    
    $height = filter_input(INPUT_POST, 'height', FILTER_VALIDATE_FLOAT);
    $weight = filter_input(INPUT_POST, 'weight', FILTER_VALIDATE_FLOAT);
    $bmi = filter_input(INPUT_POST, 'bmi', FILTER_VALIDATE_FLOAT);
    $exercise = filter_input(INPUT_POST, 'exercise', FILTER_SANITIZE_STRING);
    $water_consumption = filter_input(INPUT_POST, 'water_consumption', FILTER_VALIDATE_FLOAT);
    $date = date('Y-m-d H:i:s');

    
    if ($height === false || $weight === false || $bmi === false || empty($exercise) || $water_consumption === false) {
        header('Location: manage_body_data.php?error=Invalid input');
        exit;
    }

    
    try {
        $stmt = $pdo->prepare('INSERT INTO body_data_history (user_id, height, weight, bmi, exercise, water_consumption, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$user_id, $height, $weight, $bmi, $exercise, $water_consumption, $date]);

        
        $_SESSION['success_message'] = "Body data successfully added.";

        
        header('Location: user_dashboard.php');
        exit;
    } catch (PDOException $e) {
        die("Could not save data: " . $e->getMessage());
    }
}
?>
