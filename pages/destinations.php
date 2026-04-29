<?php
declare(strict_types=1);
session_start();

// 1. Security Headers
header("X-XSS-Protection: 1; mode=block");
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");

require_once '../config/database.php';

/**
 * THE CONNECTION BRIDGE
 * Automatically detects if your config uses $pdo or $conn
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
        // Use a unique variable name ($stmt_pic) so it doesn't overwrite your search results later
        $stmt_pic = $conn->prepare("SELECT profile_image FROM users WHERE user_id = ? LIMIT 1");
        $stmt_pic->execute([$_SESSION['user_id']]);
        $user_row = $stmt_pic->fetch();

        if ($user_row && !empty($user_row['profile_image'])) {
            $filename = $user_row['profile_image'];
            $relative_path = "../assets/uploads/profiles/" . $filename;
            
            // Physical file check using __DIR__ for accuracy
            if (file_exists(__DIR__ . '/' . $relative_path)) {
                $user_pic = $relative_path . "?v=" . time(); // Cache busting
                $_SESSION['profile_image'] = $filename; // Keep session synced
            }
        }
    } catch (PDOException $e) {
        error_log("Profile image fetch error: " . $e->getMessage());
    }
}

$search = trim($_GET['search'] ?? '');

/**
 * SQL LOGIC - Geographic Grouping
 */
$orderLogic = "
    CASE 
        WHEN location LIKE '%Batanes%' OR location LIKE '%Baguio%' OR location LIKE '%Manila%' OR location LIKE '%Union%' OR location LIKE '%Province%' THEN 1
        WHEN location LIKE '%Palawan%' OR location LIKE '%Bohol%' OR location LIKE '%Cebu%' OR location LIKE '%Iloilo%' OR location LIKE '%Negros%' THEN 2
        WHEN location LIKE '%Norte%' OR location LIKE '%Davao%' OR location LIKE '%Camiguin%' THEN 3
        WHEN location LIKE '%Japan%' OR location LIKE '%Bhutan%' OR location LIKE '%Cambodia%' OR location LIKE '%Laos%' OR location LIKE '%Indonesia%' OR location LIKE '%India%' OR location LIKE '%New Zealand%' OR location LIKE '%Australia%' THEN 4
        WHEN location LIKE '%Norway%' OR location LIKE '%Iceland%' OR location LIKE '%Portugal%' OR location LIKE '%Slovenia%' OR location LIKE '%Switzerland%' OR location LIKE '%Rwanda%' OR location LIKE '%Seychelles%' THEN 5
        ELSE 6
    END ASC, 
    eco_rating DESC, 
    name ASC";

// 5. Destination Query Logic
if (isset($conn) && $conn instanceof PDO) {
    if ($search !== '') {
        $stmt = $conn->prepare("SELECT * FROM destinations 
                                WHERE name LIKE :s1 
                                   OR location LIKE :s2 
                                   OR description LIKE :s3 
                                ORDER BY $orderLogic");
        
        $searchTerm = '%' . $search . '%';
        $stmt->execute([
            's1' => $searchTerm,
            's2' => $searchTerm,
            's3' => $searchTerm
        ]);
    } else {
        $stmt = $conn->query("SELECT * FROM destinations ORDER BY $orderLogic");
    }
    $destinations = $stmt->fetchAll();
} else {
    $destinations = [];
}

/**
 * HELPER FUNCTIONS
 */
function ecoBadge(int $rating): string {
    return str_repeat('🍃', max(1, min(5, $rating)));
}

function getRegionName(string $location): string {
    if (preg_match('/Batanes|Baguio|Manila|Union|Province/i', $location)) return "Luzon, Philippines";
    if (preg_match('/Palawan|Bohol|Cebu|Iloilo|Negros/i', $location)) return "Visayas & Palawan, Philippines";
    if (preg_match('/Norte|Davao|Camiguin/i', $location)) return "Mindanao, Philippines";
    if (preg_match('/Japan|Bhutan|Cambodia|Laos|Indonesia|India|New Zealand|Australia/i', $location)) return "Asia & Oceania";
    if (preg_match('/Norway|Iceland|Portugal|Slovenia|Switzerland|Rwanda|Seychelles/i', $location)) return "Europe & Africa";
    return "The Americas";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Destinations | Sustainable Travel Planner</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/destinations.css">
    <script src="../assets/js/main.js" defer></script>
</head>
<body>
    <header class="sub-header">
        <div class="container nav">
            <div>
                <h1 class="page-title">Eco-Rated Destinations</h1>
                <p class="tagline">Explore the world by region and sustainability rating.</p>
            </div>
            <nav class="nav-container">
            <button class="menu-toggle" aria-label="Toggle navigation">
                <span class="hamburger"></span>
            </button>

            <div class="nav-links">
                <a class="btn btn-outline" href="../index.php">Home</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a class="btn btn-outline" href="dashboard.php">Dashboard</a>
                    <a class="btn btn-outline" href="plan_trip.php">Plan Trip</a>
                    <a class="btn btn-outline" href="../auth/logout.php">Logout</a>
                    <a href="edit_user_profile.php" class="nav-profile" aria-label="Edit Profile">
                            <img src="<?php echo $user_pic; ?>" class="nav-avatar" alt="User Avatar">
                            <span class="mobile-profile-text">Edit Profile</span>
                    </a>
                  <?php endif; ?>
                </div> 
            </nav>
        </div>
    </header>

    <main class="container stack-gap">
        <section class="panel search-panel">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Search destinations..." value="<?php echo htmlspecialchars($search); ?>">
                <div class="search-btn-container">
                    <button class="custom-btn-search" type="submit">🔍︎</button>
                    <a href="destinations.php" class="custom-btn-reset">Reset</a>
                </div>
            </form>
        </section>

        <section class="destination-results">
            <?php 
            $currentRegion = '';
            if (!$destinations): 
            ?>
                <div class="panel empty-results">
                    <p class="muted">No destinations found matching your search.</p>
                </div>
            <?php 
            else: 
                foreach ($destinations as $destination): 
                    $region = getRegionName($destination['location']);
                    
                    if ($region !== $currentRegion): 
                        if ($currentRegion !== '') echo '</div>'; // Close previous grid
                        $currentRegion = $region;
            ?>
                        <h2 class="region-divider"><?php echo $currentRegion; ?></h2>
                        <div class="destination-grid">
            <?php 
                    endif; 
            ?>
                    <article class="destination-card">
                        <div class="destination-top">
                            <div class="destination-info">
                                <h3><?php echo htmlspecialchars($destination['name']); ?></h3>
                                <p class="muted">📍 <?php echo htmlspecialchars($destination['location']); ?></p>
                            </div>
                            <div class="rating-container">
                                <span class="rating-pill">
                                    <?php echo ecoBadge((int)$destination['eco_rating']); ?> 
                                    <?php echo (int)$destination['eco_rating']; ?>/5
                                </span>
                            </div>
                        </div>
                        <p><?php echo htmlspecialchars($destination['description']); ?></p>
                        <div class="note success">
                            <strong>Eco Notes:</strong> <?php echo htmlspecialchars($destination['eco_notes']); ?>
                        </div>
                    </article>
            <?php 
                endforeach; 
                echo '</div>'; // Final grid closure
            endif; 
            ?>
        </section>
    </main>

    <footer>
        <div class="container destinations-footer">
            <p class="muted">&copy; 2026 Eco-Friendly Travel Planner by Jane 🩷</p>
            <p class="muted">CMSC 207 Project</p>
        </div>
    </footer>
</body>
</html>