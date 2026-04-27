<?php
declare(strict_types=1);
session_start();

$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? $_SESSION['user_name'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eco-Friendly Travel Planner</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container nav">
            <div>
                <h1 class="brand">Eco-Friendly Travel Planner</h1>
                <p class="tagline">Plan greener trips and choose eco-friendlier transport.</p>
            </div>
            <nav class="nav-links">
                <?php if ($isLoggedIn): ?>
                    <a class="btn btn-outline" href="pages/dashboard.php">Dashboard</a>
                    <a class="btn btn-primary" href="auth/logout.php">Logout</a>
                <?php else: ?>
                    <a class="btn btn-outline" href="auth/login.php">Login</a>
                    <a class="btn btn-primary" href="auth/register.php">Register</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main class="container hero-grid">
        <section class="hero-card">
            <span class="eyebrow">Redefining the Way You Wander</span>
        
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
            <a class="btn btn-primary" href="pages/destinations.php">Browse Destinations</a>
            <a class="btn btn-outline" href="pages/plan_trip.php">Plan a Trip</a>
            </div>
        
            <?php if ($isLoggedIn): ?>
            <p class="note success">
                Welcome back, <?php echo htmlspecialchars($userName); ?>!
            </p>
            <?php else: ?>
            <p class="note">
                Join our community to curate your sustainable itinerary and unlock tailored travel insights.
            </p>
            <?php endif; ?>
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
