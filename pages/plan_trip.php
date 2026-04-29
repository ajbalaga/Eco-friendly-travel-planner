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
        // Use unique variable $stmt_pic to avoid overwriting $stmt used for trips below
        $stmt_pic = $conn->prepare("SELECT profile_image FROM users WHERE user_id = ? LIMIT 1");
        $stmt_pic->execute([$_SESSION['user_id']]);
        $user_row = $stmt_pic->fetch();

        if ($user_row && !empty($user_row['profile_image'])) {
            $filename = $user_row['profile_image'];
            $relative_path = "../assets/uploads/profiles/" . $filename;
            
            // Check if file physically exists on the disk
            if (file_exists(__DIR__ . '/' . $relative_path)) {
                $user_pic = $relative_path . "?v=" . time(); // Cache busting
                $_SESSION['profile_image'] = $filename; // Sync session
            }
        }
    } catch (PDOException $e) {
        error_log("Profile image fetch error: " . $e->getMessage());
    }
}

// 5. Trip Planning Logic
function formatForInput($dateString, $default) {
    if (empty($dateString)) return $default;
    return date('Y-m-d\TH:i', strtotime($dateString));
}

$date = new DateTime('tomorrow');
$date->modify('+1 day'); 
$min_datetime = $date->format('Y-m-d\TH:i');

// EDIT LOGIC: Check if we are editing an existing trip
$trip_id = $_GET['trip_id'] ?? null;
$edit_trip = null;

if ($trip_id && isset($conn) && $conn instanceof PDO) {
    $stmt = $conn->prepare('SELECT * FROM trips WHERE trip_id = :trip_id AND user_id = :user_id');
    $stmt->execute(['trip_id' => $trip_id, 'user_id' => $_SESSION['user_id']]);
    $edit_trip = $stmt->fetch();
}

// Fetch Destinations for the dropdown
$destinations = [];
if (isset($conn) && $conn instanceof PDO) {
    $destinations = $conn->query('SELECT destination_id, name, location, eco_rating FROM destinations ORDER BY eco_rating DESC, name ASC')->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $edit_trip ? 'Edit' : 'Plan'; ?> Your Journey | Sustainable Travel Planner</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/plan_trip.css">
    <script src="../assets/js/main.js" defer></script>
</head>
<body class="eco-theme">
    <header class="sub-header">
        <div class="container nav">
            <div class="header-text">
                <h1 class="page-title"><?php echo $edit_trip ? 'Update Journey' : 'Journey Consciously'; ?></h1>
                <p class="tagline">Plan or refine your trip to see your updated environmental impact.</p>
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
                    <a href="edit_user_profile.php" class="nav-profile" aria-label="Edit Profile">
                            <img src="<?php echo $user_pic; ?>" class="nav-avatar" alt="User Avatar">
                            <span class="mobile-profile-text">Edit Profile</span>
                    </a>
                </div> 
            </nav>
        </div>
    </header>

    <main class="container single-column">
        <?php if (isset($_GET['error'])): ?>
            
            <?php if ($_GET['error'] === 'overlap'): ?>
                <div class="alert-error">
                    <strong><i class="fas fa-exclamation-circle"></i>  Scheduling Conflict:</strong> You already have a trip saved for these dates.
                </div>

            <?php elseif ($_GET['error'] === 'invalid_date' && empty($trip_id)): ?>
                <div class="alert-error">
                    <strong><i class="fas fa-exclamation-circle"></i> Date Conflict:</strong> New trips must be scheduled at least one day ahead of today.
                </div>
            <?php endif; ?>

        <?php endif; ?>

        <section class="panel glass-card">
            <form id="tripForm" method="POST" action="trip_results.php" class="form-grid">
                <?php if ($edit_trip): ?>
                    <input type="hidden" name="trip_id" value="<?php echo $edit_trip['trip_id']; ?>">
                <?php endif; ?>

                <div class="form-group full-width">
                    <label for="destination_select">Where are you heading?</label>
                    <select name="destination_id" id="destination_select" required>
                        <option value="" data-dist="0">Choose a destination</option>
                        <?php foreach ($destinations as $destination): 
                            $name = $destination['name'];
                            $loc = $destination['location'];
                            
                            // Default distance (km)
                            $dist = 20; 

                            // --- Philippines: Luzon ---
                            if (strpos($name, 'Batanes') !== false) $dist = 650;
                            elseif (strpos($name, 'Baguio') !== false) $dist = 245;
                            elseif (strpos($name, 'Intramuros') !== false) $dist = 5;
                            elseif (strpos($name, 'La Union') !== false) $dist = 270;
                            elseif (strpos($name, 'Sagada') !== false) $dist = 390;

                            // --- Philippines: Visayas & Palawan ---
                            elseif (strpos($name, 'Bohol') !== false) $dist = 630;
                            elseif (strpos($name, 'Palawan') !== false) $dist = 600;
                            elseif (strpos($name, 'Cebu') !== false) $dist = 570;
                            elseif (strpos($name, 'Iloilo') !== false) $dist = 450;
                            elseif (strpos($name, 'Apo Island') !== false) $dist = 620;

                            // --- Philippines: Mindanao ---
                            elseif (strpos($name, 'Siargao') !== false) $dist = 800;
                            elseif (strpos($name, 'Davao') !== false) $dist = 945;
                            elseif (strpos($name, 'Camiguin') !== false) $dist = 720;
                            elseif (strpos($name, 'Hamiguitan') !== false) $dist = 1050;

                            // --- Asia & Oceania ---
                            elseif (strpos($loc, 'Japan') !== false) $dist = 3000;
                            elseif (strpos($loc, 'Bhutan') !== false) $dist = 3500;
                            elseif (strpos($loc, 'Cambodia') !== false) $dist = 1800;
                            elseif (strpos($loc, 'Laos') !== false) $dist = 1900;
                            elseif (strpos($loc, 'Indonesia') !== false) $dist = 2700;
                            elseif (strpos($loc, 'India') !== false) $dist = 4500;
                            elseif (strpos($loc, 'New Zealand') !== false) $dist = 8500;
                            elseif (strpos($loc, 'Australia') !== false) $dist = 6500;

                            // --- Europe & Africa ---
                            elseif (strpos($loc, 'Norway') !== false) $dist = 9500;
                            elseif (strpos($loc, 'Iceland') !== false) $dist = 10500;
                            elseif (strpos($loc, 'Portugal') !== false) $dist = 12000;
                            elseif (strpos($loc, 'Slovenia') !== false) $dist = 10000;
                            elseif (strpos($loc, 'Switzerland') !== false) $dist = 10200;
                            elseif (strpos($loc, 'Rwanda') !== false) $dist = 11000;
                            elseif (strpos($loc, 'Seychelles') !== false) $dist = 7800;

                            // --- The Americas ---
                            elseif (strpos($loc, 'Costa Rica') !== false) $dist = 17500;
                            elseif (strpos($loc, 'Ecuador') !== false) $dist = 17800;
                            elseif (strpos($loc, 'Chile') !== false) $dist = 18500;
                            elseif (strpos($loc, 'Mexico') !== false) $dist = 14000;
                            elseif (strpos($loc, 'Canada') !== false) $dist = 10500;
                            
                            $selected = (isset($edit_trip) && $edit_trip['destination_id'] == $destination['destination_id']) ? 'selected' : '';
                        ?>
                        <option value="<?php echo (int) $destination['destination_id']; ?>" 
                                    data-dist="<?php echo $dist; ?>" 
                                    <?php echo $selected; ?>>
                                <?php echo htmlspecialchars($destination['name'] . ' — ' . $destination['location']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="full-width date-grid">
                    <div class="form-group">
                        <label for="travel_date">Departure Date & Time</label>
                        <input type="datetime-local" 
                            name="travel_date" 
                            id="travel_date" 
                            <?php echo !$edit_trip ? 'min="' . $min_datetime . '"' : ''; ?>
                            value="<?php echo formatForInput($edit_trip['travel_date'] ?? '', $min_datetime); ?>" 
                            required>
                    </div>
                    
                    <div class="form-group">
                        <label for="return_date">Return Date & Time</label>
                        <input type="datetime-local" 
                            name="return_date" 
                            id="return_date" 
                            <?php echo !$edit_trip ? 'min="' . $min_datetime . '"' : ''; ?>
                            value="<?php echo formatForInput($edit_trip['return_date'] ?? '', ''); ?>" 
                            required>
                    </div>
                </div>

                <div class="form-group">
                    <div class="label-row" style="display: flex; justify-content: space-between; align-items: baseline;">
                        <label for="distance_km">Approximate Journey (km)</label>
                        <span class="distance-helper" onclick="fillManilaDist()">Coming from Manila? Auto-fill</span>
                    </div>
                    <input type="number" id="distance_km" name="distance_km" min="1" step="0.1" value="<?php echo $edit_trip['distance_km'] ?? ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="traveler_count">Number of Travelers</label>
                    <input type="number" name="traveler_count" id="traveler_count" min="1" value="<?php echo $edit_trip['traveler_count'] ?? '1'; ?>" required>
                </div>

                <div class="form-group">
                    <label for="transport_mode">Transit Choice</label>
                    <select name="transport_mode" id="transport_mode" required>
                        <?php 
                        $modes = ['walking', 'bike', 'public_bus', 'train', 'ferry', 'private_car', 'airplane'];
                        foreach ($modes as $mode): 
                            $sel = ($edit_trip && $edit_trip['transport_mode'] == $mode) ? 'selected' : '';
                        ?>
                        <option value="<?php echo $mode; ?>" <?php echo $sel; ?>><?php echo ucwords(str_replace('_', ' ', $mode)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="priority">Sustainability Priority</label>
                    <select name="priority" id="priority">
                        <?php $p = $edit_trip['sustainability_priority'] ?? 'carbon'; ?>
                        <option value="carbon" <?php echo $p == 'carbon' ? 'selected' : ''; ?>>Lowest Carbon Footprint</option>
                        <option value="balance" <?php echo $p == 'balance' ? 'selected' : ''; ?>>Balanced (Eco & Speed)</option>
                        <option value="local" <?php echo $p == 'local' ? 'selected' : ''; ?>>Prioritize Local Operators</option>
                    </select>
                    <div class="form-group full-width">                        
                        <div id="priority-tip" class="tip-box" style="margin-bottom: 10px; padding: 8px; background: rgba(46, 125, 50, 0.05); border-left: 3px solid #2e7d32;">
                            <small style="display: block; color: #2e7d32;">
                                <i class="icon-leaf"></i> 
                                <strong>Priority Focus:</strong> 
                                <span id="tip-text">
                                    <?php 
                                        if ($p == 'carbon') echo "Focus on minimizing emissions via land-based travel and direct routes.";
                                        elseif ($p == 'balance') echo "Optimize travel time while keeping eco-impact within acceptable limits.";
                                        else echo "Support indigenous businesses and community-led conservation efforts.";
                                    ?>
                                </span>
                            </small>
                            <small style="display: block; margin-top: 4px; color: #666; font-style: italic;">
                                <span id="notes-suggestion">
                                    <?php 
                                        if ($p == 'carbon') echo "Tip: List in notes if you're open to overland routes (like sleeper trains), public transit passes, or plant-based dining options, which drastically cut your carbon footprint.";
                                        elseif ($p == 'balance') echo "Tip: List in notes your time constraints and must-see spots. Direct transit and centrally located eco-stays to minimize daily commuting emissions.";
                                        else echo "Tip: List in notes interests like indigenous guided tours, farm-to-table dining, or artisan workshops so your travel budget directly empowers the host community.";
                                    ?>
                                </span>
                            </small>
                        </div>                        
                    </div>
                </div>

                <div class="form-group full-width">
                    <label for="notes">Trip Specifics & Preferences</label>
                    <textarea name="notes" id="notes-plan-trip" rows="4" placeholder="Tell us more about your travel style..."><?php echo htmlspecialchars($edit_trip['notes'] ?? ''); ?></textarea>
                    
                </div>

                <div class="form-footer full-width">
                    <button type="submit" class="btn btn-primary" style="background-color: #89be40; color: white; width: 100%;">
                        <?php echo $edit_trip ? 'Update & Recalculate Impact →' : 'Calculate My Impact & Save Trip →'; ?>
                    </button>
                </div>

                            
                <p class="text-center small">
                    <a href="javascript:history.back()" class="back-link">&larr; Back</a>
                </p>
            </form>
        </section>
    </main>

    <footer>
        <div class="container" style="display: flex; justify-content: space-between; padding: 2rem 0; border-top: 1px solid #eee; margin-top: 2rem;">
            <p class="muted">&copy; 2026 Eco-Friendly Travel Planner by Jane 🩷</p>
            <p class="muted">CMSC 207 Project</p>
        </div>
    </footer>

    <script>
    const tripForm = document.getElementById('tripForm');
    const travelDate = document.getElementById('travel_date');
    const returnDate = document.getElementById('return_date');
    const notesArea = document.getElementById('notes-plan-trip'); // Fixed ID
    const distInput = document.getElementById('distance_km');
    const destInput = document.getElementById('destination_select');
    const modeInput = document.getElementById('transport_mode');

    const isEdit = <?php echo $edit_trip ? 'true' : 'false'; ?>;
    
    // Capture original values to detect changes
    const original = isEdit ? {
        date: travelDate.value,
        ret: returnDate.value,
        notes: notesArea.value.trim(),
        dist: distInput.value,
        dest: destInput.value,
        mode: modeInput.value
    } : null;

    function fillManilaDist() {
        const select = document.getElementById('destination_select');
        const distInput = document.getElementById('distance_km');
        const dist = select.options[select.selectedIndex].getAttribute('data-dist');
        if (dist > 0) distInput.value = dist;
        else alert("Please select a destination first!");
    }

    travelDate.addEventListener('change', () => returnDate.min = travelDate.value);

    tripForm.addEventListener('submit', (e) => {
        if (isEdit) {
            const hasChanged = 
                travelDate.value !== original.date || 
                returnDate.value !== original.ret || 
                notesArea.value.trim() !== original.notes ||
                distInput.value !== original.dist ||
                destInput.value !== original.dest ||
                modeInput.value !== original.mode;

            if (!hasChanged) {
                e.preventDefault(); // Stop redirection if nothing changed
                alert("No changes detected. Please update a field to recalculate impact.");
            }
        }
    });
</script>
</body>
</html>