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
 * Ensures $conn is used consistently throughout the script.
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
        $stmt = $conn->prepare("SELECT profile_image FROM users WHERE user_id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $user_row = $stmt->fetch();

        if ($user_row && !empty($user_row['profile_image'])) {
            $filename = $user_row['profile_image'];
            $relative_path = "../assets/uploads/profiles/" . $filename;
            
            // Check if file physically exists on server
            if (file_exists(__DIR__ . '/' . $relative_path)) {
                $user_pic = $relative_path . "?v=" . time(); // Cache busting
                $_SESSION['profile_image'] = $filename; // Keep session in sync
            }
        }
    } catch (PDOException $e) {
        error_log("Profile image fetch error: " . $e->getMessage());
    }
}

// 5. Database Try-Catch Block for Dashboard Content
$recentTrips = [];
try {
    if (!isset($conn) || !($conn instanceof PDO)) {
        throw new Exception("Database connection is not available.");
    }

    $recentTripsStmt = $conn->prepare(
        'SELECT t.*, d.name AS destination_name, d.location, d.eco_rating
         FROM trips t
         INNER JOIN destinations d ON t.destination_id = d.destination_id
         WHERE t.user_id = :user_id
         ORDER BY t.travel_date ASC
         LIMIT 10'
    );
    $recentTripsStmt->execute(['user_id' => (int)$_SESSION['user_id']]);
    $recentTrips = $recentTripsStmt->fetchAll(PDO::FETCH_ASSOC);

    if (!is_array($recentTrips)) {
        $recentTrips = [];
    }
} catch (Exception $e) {
    error_log("Dashboard Fetch Error: " . $e->getMessage());
    // Fallback: $recentTrips stays an empty array
}

// 6. Validated Helper Functions with Strict Types
function getScoreClass(int|float $score): string {
    if ($score >= 80) return 'score-high';
    if ($score >= 50) return 'score-mid';
    return 'score-low';
}

function getPriorityIcon(?string $priority): string {
    return match(strtolower(trim($priority ?? ''))) {
        'carbon'  => '🍃',
        'local'   => '🤝',
        'balance' => '⚖️',
        default   => '📍'
    };
}

function safelyFormatDate(?string $dateString): string {
    if (empty($dateString)) return 'N/A';
    $timestamp = strtotime($dateString);
    if ($timestamp === false) return 'Invalid Date';
    return date('M j, Y, g:i A', $timestamp);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Sustainable Travel Planner</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <script src="../assets/js/main.js" defer></script>
</head>

<body>
    
    <header class="sub-header">
        <div class="container nav">
            <div>
                <h1 class="page-title">Dashboard</h1>
                <p class="tagline">Welcome back, <?php echo htmlspecialchars((string)($_SESSION['user_name'] ?? 'Traveler'), ENT_QUOTES, 'UTF-8'); ?>! 👋</p>
            </div>
            <nav class="nav-container">
                <button class="menu-toggle" aria-label="Toggle navigation">
                    <span class="hamburger"></span>
                </button>

                <div class="nav-links">
                    <a class="btn btn-outline" href="../index.php">Home</a>
                    <a class="btn btn-outline" href="destinations.php">Destinations</a>
                    <a class="btn btn-outline" href="plan_trip.php">Plan Trip</a>   
                    <a class="btn btn-outline" href="../auth/logout.php">Logout</a>
                    <a href="edit_user_profile.php" class="nav-profile" aria-label="Edit Profile">
                            <img src="<?php echo $user_pic; ?>" class="nav-avatar" alt="User Avatar">
                            <span class="mobile-profile-text">Edit Profile</span>
                    </a>
                </div>
            </nav>
        </div>
    </header>

    <main class="container dashboard-grid">
    <section class="dashboard-main-content">
        <div class="header-flex">
            <h2 class="panel-title">Recent Itineraries</h2>
            <?php if (!empty($recentTrips)): ?>
                <span class="count-badge"><?php echo count($recentTrips); ?> Total</span>
            <?php endif; ?>
        </div>

        <?php if (empty($recentTrips)): ?>
            <div class="empty-state">
                <p>No upcoming trips saved yet.</p>
                <a href="plan_trip.php" class="btn btn-primary">Start Planning</a>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
    <table class="itinerary-table">
        <thead>
            <tr>
                <th>Destination & Details</th>
                <th>Travel Date</th>
                <th>Carbon Impact</th>
                <th>Sustainability</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recentTrips as $trip): ?>
                <tr class="main-data-row">
                    <td class="dest-cell">
                        <span class="dest-name">
                            <?php echo htmlspecialchars((string)$trip['destination_name'], ENT_QUOTES, 'UTF-8'); ?>
                            <?php echo getPriorityIcon($trip['sustainability_priority'] ?? null); ?>
                        </span>
                        <div class="meta-info">
                            <span>🚗 <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', (string)($trip['transport_mode'] ?? 'unknown'))), ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="dot">•</span>
                            <span>👥 <?php echo (int)($trip['traveler_count'] ?? 1); ?> Traveler(s)</span>
                        </div>
                    </td>
                    
                    <td class="date-cell" data-label="Travel Date">
                        <span class="date-text">
                            <?php 
                                $start = safelyFormatDate($trip['travel_date'] ?? null);
                                $end = safelyFormatDate($trip['return_date'] ?? null);
                                echo htmlspecialchars($start . ' to ' . $end, ENT_QUOTES, 'UTF-8'); 
                            ?>
                        </span>
                    </td>
                                        
                    <td class="impact-cell" data-label="Carbon Impact">
                        <?php $co2 = (float)($trip['carbon_footprint_kg'] ?? 0); ?>
                        <div class="impact-val <?php echo $co2 > 100 ? 'impact-high' : ''; ?>">
                            <?php echo number_format($co2, 1); ?> kg
                        </div>
                        <div class="label-muted">CO₂e Net</div>
                    </td>
                    
                    <td class="rating-cell" data-label="Sustainability">
                        <?php $score = (int)($trip['sustainability_score'] ?? 0); ?>
                        <div class="rating-badge <?php echo getScoreClass($score); ?>">
                            <?php echo $score; ?>/100
                        </div>
                    </td>

                    <td class="actions-cell" data-label="Actions">
                        <div class="actions-group">
                            <a href="plan_trip.php?trip_id=<?php echo (int)$trip['trip_id']; ?>" class="btn-action btn-edit" aria-label="Edit Trip">
                                <span class="btn-icon">📝</span>
                                <span class="btn-text">Edit</span>
                            </a>
                            <form method="POST" action="delete_trip.php" onsubmit="return confirm('Are you sure you want to cancel this trip? This cannot be undone.');" style="margin:0;">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="trip_id" value="<?php echo (int)$trip['trip_id']; ?>">
                                <button type="submit" class="btn-action btn-delete" aria-label="Cancel Trip">
                                    <span class="btn-icon">❌</span>
                                    <span class="btn-text">Cancel</span>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>

                <?php if (!empty($trip['notes'])): ?>
                <tr class="notes-row">
                    <td colspan="5">
                        <div class="trip-notes">
                            <span class="notes-label">📌 Your Itinerary Notes:</span> "<?php echo htmlspecialchars((string)$trip['notes'], ENT_QUOTES, 'UTF-8'); ?>"
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
        <?php endif; ?>
        
    </section>
    </main>

    <footer>
        <div class="container dashboard-footer">
            <p class="muted">&copy; <?php echo date('Y'); ?> Eco-Friendly Travel Planner by Jane 🩷</p>
            <p class="muted">CMSC 207 Project</p>
        </div>
    </footer>
   
</body>
</html>