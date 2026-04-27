<?php
declare(strict_types=1);
session_start();
require_once '../config/database.php';

// Security: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trip_id = $_POST['trip_id'] ?? null;
    $user_id = $_SESSION['user_id'];
    
    // Sanitize and collect inputs
    $travel_date = $_POST['travel_date'];
    $return_date = $_POST['return_date'];
    $notes = $_POST['notes'];
    $priority = $_POST['priority'];
    $traveler_count = (int)$_POST['traveler_count'];

    if ($trip_id) {
        try {
            // Update the existing trip record
            $stmt = $conn->prepare('
                UPDATE trips 
                SET travel_date = :travel_date, 
                    return_date = :return_date, 
                    notes = :notes,
                    sustainability_priority = :priority,
                    traveler_count = :traveler_count
                WHERE trip_id = :trip_id AND user_id = :user_id
            ');

            $stmt->execute([
                'travel_date' => $travel_date,
                'return_date' => $return_date,
                'notes' => $notes,
                'priority' => $priority,
                'traveler_count' => $traveler_count,
                'trip_id' => $trip_id,
                'user_id' => $user_id
            ]);

            // Success: Redirect to dashboard
            header('Location: dashboard.php?msg=updated');
            exit;

        } catch (PDOException $e) {
            // Handle database errors
            die("Error updating trip: " . $e->getMessage());
        }
    }
}

// If access is attempted directly without POST, send back to dashboard
header('Location: dashboard.php');
exit;