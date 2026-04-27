<?php
declare(strict_types=1);
session_start();
require_once '../config/database.php';

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

if ($search !== '') {
    // FIX: Using unique placeholders (:s1, :s2, :s3) to prevent the HY093 error[cite: 1]
    $stmt = $conn->prepare("SELECT * FROM destinations 
                            WHERE name LIKE :s1 
                               OR location LIKE :s2 
                               OR description LIKE :s3 
                            ORDER BY $orderLogic");
    
    $searchTerm = '%' . $search . '%';
    
    // FIX: Mapping the search term to each unique placeholder[cite: 1]
    $stmt->execute([
        's1' => $searchTerm,
        's2' => $searchTerm,
        's3' => $searchTerm
    ]);
} else {
    $stmt = $conn->query("SELECT * FROM destinations ORDER BY $orderLogic");
}

$destinations = $stmt->fetchAll();

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
</head>
<body>
    <header class="sub-header">
        <div class="container nav">
            <div>
                <h1 class="page-title">Eco-Rated Destinations</h1>
                <p class="tagline">Explore the world by region and sustainability rating.</p>
            </div>
            <nav class="nav-links">
                <a class="btn btn-outline" href="../index.php">Home</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a class="btn btn-outline" href="dashboard.php">Dashboard</a>
                    <a class="btn btn-primary" href="plan_trip.php">Plan Trip</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="container stack-gap">
        <section class="panel search-panel">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Search destinations..." value="<?php echo htmlspecialchars($search); ?>">
                <button class="btn btn-primary" type="submit">Search</button>
                <a class="btn btn-outline" href="destinations.php">Reset</a>
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