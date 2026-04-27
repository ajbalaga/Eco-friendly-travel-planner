<?php
declare(strict_types=1);
session_start();
require_once '../config/database.php';

// Check if user is logged in and trip_id is provided
if (isset($_SESSION['user_id']) && isset($_POST['trip_id'])) {
    $stmt = $conn->prepare('DELETE FROM trips WHERE trip_id = :trip_id AND user_id = :user_id');
    $stmt->execute([
        'trip_id' => $_POST['trip_id'],
        'user_id' => $_SESSION['user_id']
    ]);
}

// Redirect back to dashboard
header('Location: dashboard.php');
exit;