<?php
declare(strict_types=1);

// 1. Security Headers
header("X-XSS-Protection: 1; mode=block");
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");

session_start();
require_once '../config/database.php';

/**
 * THE CONNECTION BRIDGE
 * Ensures $conn is defined regardless of the variable name in database.php
 */
if (!isset($conn)) {
    if (isset($pdo)) { $conn = $pdo; }
    elseif (isset($db)) { $conn = $db; }
}

// 2. Strict Authentication Validation
if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
    session_destroy();
    header('Location: ../auth/login.php');
    exit;
}

// 3. CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        $_SESSION['csrf_token'] = md5(uniqid((string)mt_rand(), true)); 
    }
}

// 4. Secure Profile Image Assignment (FETCH FROM DATABASE)
$default_avatar = "../assets/images/default-avatar.png";
$user_pic = $default_avatar;

if (isset($conn) && $conn instanceof PDO) {
    try {
        // We use $stmt_pic to avoid any conflict with variables below
        $stmt_pic = $conn->prepare("SELECT profile_image FROM users WHERE user_id = ? LIMIT 1");
        $stmt_pic->execute([$_SESSION['user_id']]);
        $user_row = $stmt_pic->fetch();

        if ($user_row && !empty($user_row['profile_image'])) {
            $filename = $user_row['profile_image'];
            $relative_path = "../assets/uploads/profiles/" . $filename;
            
            if (file_exists(__DIR__ . '/' . $relative_path)) {
                $user_pic = $relative_path . "?v=" . time();
                $_SESSION['profile_image'] = $filename; // Keep session synced
            }
        }
    } catch (PDOException $e) {
        error_log("Profile image fetch error: " . $e->getMessage());
    }
}

// 5. POST Request Validation
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: plan_trip.php');
    exit;
}

// 6. Capture Inputs
$tripId = isset($_POST['trip_id']) ? (int)$_POST['trip_id'] : null;
$destinationId = (int) ($_POST['destination_id'] ?? 0);
$travelDate = trim($_POST['travel_date'] ?? '');
$returnDate = trim($_POST['return_date'] ?? ''); 
$transportMode = trim($_POST['transport_mode'] ?? '');
$distanceKm = (float) ($_POST['distance_km'] ?? 0);
$travelerCount = (int) ($_POST['traveler_count'] ?? 1); 
$priority = trim($_POST['priority'] ?? 'carbon'); 
$notes = trim($_POST['notes'] ?? '');

if ($destinationId <= 0 || $travelDate === '' || $transportMode === '' || $distanceKm <= 0) {
    header('Location: plan_trip.php');
    exit;
}

// --- OVERLAP PREVENTION LOGIC ---
if (isset($conn) && $conn instanceof PDO) {
    $checkEnd = !empty($returnDate) ? $returnDate : $travelDate;
    $overlapSql = "SELECT COUNT(*) FROM trips 
                   WHERE user_id = :user_id 
                   AND (:new_start <= return_date AND :new_end >= travel_date)";

    if ($tripId) { $overlapSql .= " AND trip_id != :trip_id"; }

    $overlapStmt = $conn->prepare($overlapSql);
    $overlapParams = ['user_id' => $_SESSION['user_id'], 'new_start' => $travelDate, 'new_end' => $checkEnd];
    if ($tripId) { $overlapParams['trip_id'] = $tripId; }

    $overlapStmt->execute($overlapParams);
    if ($overlapStmt->fetchColumn() > 0) {
        $redirectUrl = "plan_trip.php?error=overlap" . ($tripId ? "&trip_id=$tripId" : "");
        header("Location: $redirectUrl");
        exit;
    }

    // Fetch destination
    $destinationStmt = $conn->prepare('SELECT * FROM destinations WHERE destination_id = :destination_id LIMIT 1');
    $destinationStmt->execute(['destination_id' => $destinationId]);
    $destination = $destinationStmt->fetch();

    if (!$destination) {
        header('Location: plan_trip.php');
        exit;
    }
}

// 7. Emission & Scoring Logic
$emissionFactors = [
    'walking' => 0, 'bike' => 0, 'public_bus' => 0.10, 'train' => 0.05,
    'ferry' => 0.12, 'private_car' => 0.21, 'motorcycle' => 0.12, 'airplane' => 0.25,
];

$baseScoreMap = [
    'walking' => 100, 'bike' => 95, 'train' => 85, 'public_bus' => 75,
    'ferry' => 65, 'motorcycle' => 55, 'private_car' => 40, 'airplane' => 25,
];

$estimatedEmission = ($emissionFactors[$transportMode] ?? 0.15) * $distanceKm * $travelerCount;

// Logarithmic Penalty for distance
$distancePenalty = (int) round(8 * log10($distanceKm + 1));
$distancePenalty = min(40, $distancePenalty); 

$ecoBonus = ((int) ($destination['eco_rating'] ?? 0)) * 3;
$sustainabilityScore = ($baseScoreMap[$transportMode] ?? 50) - $distancePenalty + $ecoBonus;
$sustainabilityScore = (int) max(5, min(100, $sustainabilityScore));

// 8. Save Logic
if (isset($conn) && $conn instanceof PDO) {
    if ($tripId) {
        $saveStmt = $conn->prepare(
            'UPDATE trips SET destination_id = :destination_id, travel_date = :travel_date, 
             return_date = :return_date, transport_mode = :transport_mode, distance_km = :distance_km, 
             traveler_count = :traveler_count, sustainability_priority = :priority, 
             carbon_footprint_kg = :carbon, notes = :notes, sustainability_score = :score 
             WHERE trip_id = :trip_id AND user_id = :user_id'
        );
        $params = [
            'destination_id' => $destinationId, 'travel_date' => $travelDate,
            'return_date' => !empty($returnDate) ? $returnDate : null,
            'transport_mode' => $transportMode, 'distance_km' => $distanceKm,
            'traveler_count' => $travelerCount, 'priority' => $priority,
            'carbon' => $estimatedEmission, 'notes' => $notes,
            'score' => $sustainabilityScore, 'trip_id' => $tripId, 'user_id' => $_SESSION['user_id']
        ];
    } else {
        $saveStmt = $conn->prepare(
            'INSERT INTO trips (user_id, destination_id, travel_date, return_date, transport_mode, 
             distance_km, traveler_count, sustainability_priority, carbon_footprint_kg, notes, sustainability_score) 
             VALUES (:user_id, :destination_id, :travel_date, :return_date, :transport_mode, 
             :distance_km, :traveler_count, :priority, :carbon, :notes, :score)'
        );
        $params = [
            'user_id' => $_SESSION['user_id'], 'destination_id' => $destinationId,
            'travel_date' => $travelDate, 'return_date' => !empty($returnDate) ? $returnDate : null,
            'transport_mode' => $transportMode, 'distance_km' => $distanceKm,
            'traveler_count' => $travelerCount, 'priority' => $priority,
            'carbon' => $estimatedEmission, 'notes' => $notes, 'score' => $sustainabilityScore
        ];
    }
    $saveStmt->execute($params);
    
   
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trip Analysis | Sustainable Travel Planner</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/trip_results.css">
    <script src="../assets/js/main.js" defer></script>
</head>
<body>
    <header class="sub-header">
        <div class="container nav">
            <div>
                <h1 class="page-title">Trip Analysis</h1>
                <p class="tagline"><?php echo $tripId ? 'Your updates were saved!' : 'Your itinerary has been analyzed and saved.'; ?></p>
            </div>
            
            <nav class="nav-container">
                <button class="menu-toggle" aria-label="Toggle navigation">
                    <span class="hamburger"></span>
                </button>

                <div class="nav-links">
                    <a class="btn btn-outline" href="../index.php">Home</a> 
                    <a class="btn btn-outline" href="destinations.php">Destinations</a>
                    <a class="btn btn-outline" href="dashboard.php">Dashboard</a>
                    <a class="btn btn-outline" href="../auth/logout.php">Logout</a>
                    <a class="btn btn-primary" href="plan_trip.php">Plan New Trip</a>
                    <a href="edit_user_profile.php" class="nav-profile" aria-label="Edit Profile">
                            <img src="<?php echo $user_pic; ?>" class="nav-avatar" alt="User Avatar">
                            <span class="mobile-profile-text">Edit Profile</span>
                    </a>
                </div> </nav>
        </div>
    </header>

    <main class="container trip-results-main">
        <!-- Main Result Panel -->
        <section class="result-header-panel">
            <div>
                <span class="eyebrow" style="color: #89be40; letter-spacing: 1px; font-weight: bold; font-size: 0.75rem;">PLANNED DESTINATION</span>
                <h2 style="margin: 0.5rem 0; font-size: 2rem;"><?php echo htmlspecialchars($destination['name']); ?></h2>
                <p class="muted" style="font-size: 1.1rem;">📍 <?php echo htmlspecialchars($destination['location']); ?></p>
            </div>
            <div style="text-align: right;">
                <span style="display: block; font-size: 0.9rem; color: #666; margin-bottom: 0.5rem;">Sustainability Score</span>
                <div class="score-value"><?php echo $sustainabilityScore; ?><span style="font-size: 1.2rem; color: #ccc;">/100</span></div>
            </div>
        </section>

        <div class="analysis-grid">
            <!-- Left: Journey Stats -->
            <section class="panel">
                <h3 style="margin-top: 0; color: #2d3748;">Journey Insights</h3>
                <div class="stat-grid">
                    <div class="stat-item">
                        <small class="muted" style="display:block; text-transform:uppercase;">Footprint</small>
                        <strong style="color: #e53e3e; font-size: 1.2rem;"><?php echo number_format($estimatedEmission, 1); ?> kg CO₂e</strong>
                    </div>
                    <div class="stat-item">
                        <small class="muted" style="display:block; text-transform:uppercase;">Transport</small>
                        <strong style="font-size: 1.2rem;"><?php echo ucwords(str_replace('_', ' ', $transportMode)); ?></strong>
                    </div>
                    <div class="stat-item">
                        <small class="muted" style="display:block; text-transform:uppercase;">Distance</small>
                        <strong style="font-size: 1.2rem;"><?php echo number_format($distanceKm, 1); ?> km</strong>
                    </div>
                    <div class="stat-item">
                        <small class="muted" style="display:block; text-transform:uppercase;">Eco-Rating</small>
                        <strong style="font-size: 1.2rem;"><?php echo str_repeat('🍃', (int)$destination['eco_rating']); ?></strong>
                    </div>
                </div>

                <?php if (!empty($suggestion)): ?>
                    <div class="tip-box">
                        <?php echo $suggestion; ?>
                    </div>
                <?php endif; ?>

                <div class="disclaimer-text">
                    <strong>Disclaimer:</strong> 
                    Carbon footprints and sustainability scores are estimated. Our current calculation uses average emission factors and assumes your single selected transport mode is used for the entire distance. Actual emissions may vary if multiple transport types are used during the trip.
                </div>
            </section>

            <!-- Right: Sustainable Tips -->
            <section class="panel eco-notes-panel">
                <h3 style="color: #2e7d32; margin-top: 0;">🌿 Sustainable Tourism Tips</h3>
                <p style="line-height: 1.7; color: #2d3748;">
                    <?php echo htmlspecialchars($destination['eco_notes'] ?? 'Explore responsibly by minimizing waste and respecting local cultural sites.'); ?>
                </p>
                <div class="note success" style="margin-top: 1rem; background: rgba(255,255,255,0.5);">
                    Small choices like bringing reusable water bottles or staying in locally-owned lodges make a massive difference.
                </div>
            </section>
        </div>
        
        <!-- Planning Notes -->
        <?php if (!empty($notes)): ?>
            <section class="panel notes-preview">
                <strong>Your Itinerary Notes</strong>
                <p><?php echo htmlspecialchars($notes); ?></p>
            </section>
        <?php endif; ?>

        <p class="text-center small">
            <a href="dashboard.php" class="back-link">&larr; View Itineraries</a>
        </p>
    </main>

    <footer>
        <div class="container result-footer">
            <p>&copy; 2026 Eco-Friendly Travel Planner by Jane 🩷</p>
            <p>CMSC 207 Project</p>
        </div>
    </footer>
</body>
</html>