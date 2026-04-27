<?php
declare(strict_types=1);
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Fetching trips sorted by Travel Date (Upcoming First)
$recentTripsStmt = $conn->prepare(
    'SELECT t.*, d.name AS destination_name, d.location, d.eco_rating
     FROM trips t
     INNER JOIN destinations d ON t.destination_id = d.destination_id
     WHERE t.user_id = :user_id
     ORDER BY t.travel_date ASC
     LIMIT 10'
);
$recentTripsStmt->execute(['user_id' => $_SESSION['user_id']]);
$recentTrips = $recentTripsStmt->fetchAll();

// Helpers for UI formatting
function getScoreClass($score) {
    if ($score >= 80) return 'score-high';
    if ($score >= 50) return 'score-mid';
    return 'score-low';
}

function getPriorityIcon($priority) {
    return match($priority) {
        'carbon' => '🍃',
        'local'  => '🤝',
        'balance' => '⚖️',
        'default' => '📍'
    };
}

function formatDatePeriod($start, $end) {
    $startDate = new DateTime($start);
    if (empty($end) || $start === $end) {
        return $startDate->format('M d, Y');
    }
    $endDate = new DateTime($end);
    return $startDate->format('M d') . ' – ' . $endDate->format('M d, Y');
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
</head>
<body>
    <header class="sub-header">
        <div class="container nav">
            <div>
                <h1 class="page-title">Dashboard</h1>
                <p class="tagline">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Traveler'); ?>! 👋</p>
            </div>
            <nav class="nav-links">
                <a class="btn btn-outline" href="../index.php">Home</a>
                <a class="btn btn-outline" href="destinations.php">Destinations</a>
                <a class="btn btn-primary" href="plan_trip.php">Plan Trip</a>
                <a class="btn btn-outline" href="../auth/logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <main class="container dashboard-grid">
        <aside class="panel dashboard-aside">
            <h2 class="panel-title">Quick Actions</h2>
            <div class="quick-actions">
                <a class="action-card" href="destinations.php">
                    <strong>🌍 Browse Destinations</strong>
                    <span>Explore eco-ratings and find sustainable gems.</span>
                </a>
                <a class="action-card" href="plan_trip.php">
                    <strong>📋 New Trip Plan</strong>
                    <span>Calculate footprint and build your next adventure.</span>
                </a>
            </div>
        </aside>

        <section class="panel dashboard-main-content">
            <h2 class="panel-title">Recent Itineraries</h2>
            <?php if (!$recentTrips): ?>
                <div class="empty-state">
                    <p class="muted">No upcoming trips saved yet.</p>
                    <a href="plan_trip.php" class="btn btn-primary">Start Planning</a>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Destination & Details</th>
                                <th>Date</th>
                                <th>Impact</th>
                                <th>Sustainability</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentTrips as $trip): ?>
                                <tr>
                                    <td class="destination-cell">
                                        <strong><?php echo htmlspecialchars($trip['destination_name']); ?></strong>
                                        <span title="Priority: <?php echo htmlspecialchars($trip['sustainability_priority'] ?? 'Standard'); ?>">
                                            <?php echo getPriorityIcon($trip['sustainability_priority'] ?? ''); ?>
                                        </span>
                                        <br>
                                        <span class="small muted">
                                            <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $trip['transport_mode']))); ?> • 
                                            <?php echo (int)($trip['traveler_count'] ?? 1); ?> traveler(s)
                                        </span>

                                        <?php if (!empty($trip['notes'])): ?>
                                            <div class="trip-notes">
                                                "<?php echo htmlspecialchars($trip['notes']); ?>"
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="date-cell">
                                        <?php echo formatDatePeriod($trip['travel_date'], $trip['return_date'] ?? null); ?>
                                    </td>
                                    
                                    <td>
                                        <?php $co2 = (float)($trip['carbon_footprint_kg'] ?? 0); ?>
                                        <span class="impact-value" style="<?php echo $co2 > 100 ? 'color: #d9534f;' : ''; ?>">
                                            <?php echo number_format($co2, 1); ?> kg
                                        </span><br>
                                        <span class="small muted">CO₂e emitted</span>
                                    </td>
                                    
                                    <td>
                                        <div class="rating-pill <?php echo getScoreClass((int)$trip['sustainability_score']); ?>">
                                            <?php echo (int)$trip['sustainability_score']; ?>/100
                                        </div>
                                    </td>

                                    <td class="actions-cell">
                                        <div class="actions-wrapper">
                                            <a href="plan_trip.php?trip_id=<?php echo $trip['trip_id']; ?>" class="btn-edit">
                                                ✏️ Update
                                            </a>
                                            <form method="POST" action="delete_trip.php" style="margin: 0;" 
                                                  onsubmit="return confirm('Cancel this trip?');">
                                                <input type="hidden" name="trip_id" value="<?php echo $trip['trip_id']; ?>">
                                                <button type="submit" class="btn-cancel">❌ Cancel</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <div class="container dashboard-footer">
            <p class="muted">&copy; 2026 Eco-Friendly Travel Planner by Jane 🩷</p>
            <p class="muted">CMSC 207 Project</p>
        </div>
    </footer>
</body>
</html>