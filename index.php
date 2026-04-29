<?php
declare(strict_types=1);

// 1. Security Headers
header("X-XSS-Protection: 1; mode=block");
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");

session_start();

// Adjusted path: Ensure this points to your actual config location
require_once 'config/database.php'; 

/**
 * THE CONNECTION BRIDGE
 * Automatically detects if your config uses $conn or $db and assigns it to $pdo
 */
if (!isset($pdo)) {
    if (isset($conn)) { $pdo = $conn; }
    elseif (isset($db)) { $pdo = $db; }
}

// 2. Authentication State
$isLoggedIn = isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']);
$userName = $isLoggedIn ? ($_SESSION['user_name'] ?? 'User') : '';

// 3. CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 4. Robust Profile Image Logic (Direct Database Fetch)
$default_avatar = "assets/images/default-avatar.png";
$user_pic = $default_avatar;

if ($isLoggedIn && isset($pdo)) {
    try {
        // Fetch the filename directly from the database for the most current data
        $stmt = $pdo->prepare("SELECT profile_image FROM users WHERE user_id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $user_data = $stmt->fetch();

        if ($user_data && !empty($user_data['profile_image'])) {
            $filename = $user_data['profile_image'];
            $relative_path = "assets/uploads/profiles/" . $filename;
            
            // Physical File Check: verify file exists on server disk
            if (file_exists(__DIR__ . '/' . $relative_path)) {
                $user_pic = $relative_path . "?v=" . time(); // Cache busting
                
                // Sync session so other pages (like dashboard) stay updated
                $_SESSION['profile_image'] = $filename;
            }
        }
    } catch (PDOException $e) {
        // Fallback to default if DB query fails
        error_log("Profile image query failed: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eco-Friendly Travel Planner</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/main.js" defer></script>
</head>
<body>
    <header class="site-header">
        <div class="container nav">
            <div>
                <h1 class="brand">Eco-Friendly Travel Planner</h1>
                <p class="tagline">Plan greener trips and choose eco-friendlier transport.</p>
            </div>
            
            <nav class="nav-container">
                <button class="menu-toggle" aria-label="Toggle navigation">
                    <span class="hamburger"></span>
                </button>

                <div class="nav-links">
                    <?php if ($isLoggedIn): ?>
                        <a class="btn btn-outline" href="pages/dashboard.php">Dashboard</a>
                        <a class="btn btn-outline" href="auth/logout.php">Logout</a>
                        <a href="pages/edit_user_profile.php" class="nav-profile" aria-label="Edit Profile">
                                <img src="<?php echo $user_pic; ?>" class="nav-avatar" alt="User Avatar">
                                <span class="mobile-profile-text">Edit Profile</span>
                        </a>
                    <?php else: ?>
                        <a class="btn btn-outline" href="auth/login.php">Login</a>
                        <a class="btn btn-outline" href="auth/register.php">Register</a>
                    <?php endif; ?>
                </div> 
            </nav>
        </div>
    </header>
    <main class="container hero-grid">
        <section class="hero-card">
            <?php if ($isLoggedIn): ?>
            <p class="note logged-in-note">
                Welcome back, <?php echo htmlspecialchars($userName); ?>!
            </p>
            <?php else: ?>
            <span class="eyebrow">Redefining the Way You Wander</span>
            <?php endif; ?>
        
            <h1>Travel Smarter, Lighter, and More Sustainably</h1>
        
            <p class="hero-text">
             Discover destinations that align with your values. From renewable energy to local sourcing, we help you plan trips that leave a positive footprint.
            </p>
        
            <div class="requirements-box">
                <h3>What we care about:</h3>
                <ul class="requirements-list">
                    <li>Energy: Clean Power & Solar Integration</li>
                    <li>Sourcing: Organic, Farm-to-Table, & Local Goods</li>
                    <li>Impact: Waste Management & Non-toxic Care</li>
                    <li>Design: Sustainable Architecture & Low-Impact Living</li>
                </ul>
            </div>
        
            <div class="hero-actions">
            <a class="btn btn-outline" href="pages/destinations.php">Browse Destinations</a>
            <a class="btn btn-primary" href="pages/plan_trip.php">Plan a Trip</a>
            </div>        
            
            <p class="note">
                Join our community to curate your sustainable itinerary and unlock tailored travel insights.
            </p>
        </section>    
        <aside class="info-panel">
            <div class="mini-card">
                <h3>Core Features</h3>
                <ul class="feature-list">
                    <li>User registration and secure login</li>
                    <li>Destination database with eco-ratings</li>
                    <li>Trip planner with transport suggestions</li>
                    <li>Saved trip history for logged-in users</li>
                </ul>
            </div>        
            <div class="mini-card">
            <h3>Sustainability Tips</h3>
             <ul class="feature-list">
                <li>Replacing paper with digital copies, phone apps and cloud storage systems provide secure, quick access to important information without the need for printouts</li>
                <li>Travel by train or bus, which emit fewer greenhouse gases than planes or cars. If flying is necessary, select direct flights and consider Carbon Offset Programs</li>
                <li>Support local businesses, prioritize hiring local guides and taking community-led tours to ensure funds remain within the community</li>
                <li>Respect wildlife and natural habitats to provide a better understanding of why certain species flourish there and how they contribute to the ecosystem and local societies</li>
                <li>Choose accommodations that have strong sustainability practices, such as those using renewable energy, implementing water conservation measures, and supporting local communities</li>
                <li>Pack light to reduce fuel consumption and emissions, and consider using eco-friendly luggage made from sustainable materials</li>
                <li>Use reusable items like water bottles, shopping bags, and utensils to minimize single-use plastics and reduce waste during your travels</li>
            </ul>
            </div>
        </aside>
    </main>
    <footer>
        <div class="container">
            <p>&copy; 2026 Eco-Friendly Travel Planner by Jane 🩷</p>
            
            <div class="footer-links">            
                <a>CMSC 207 Project</a>
            </div>
        </div>
    </footer>
</body>
</html>
