<?php
$pageTitle = "Home";
include_once __DIR__ . '/includes/header.php';

// Include functions for welcome message
require_once __DIR__ . '/includes/functions.php';

$isLoggedIn = isset($_SESSION['user_id']);
$welcomeMsg = "";
if ($isLoggedIn) {
    $welcomeMsg = getRandomWelcome($_SESSION['full_name']);
}
?>

<section class="hero">
    <h1>&#9876; Adventurer's Guild &#9876;</h1>
    <p>Welcome to the Adventurer's Guild Registration & Quest Board. Join our fellowship of brave adventurers, explore epic quests, and forge your legend in the realm.</p>
    <div class="hero-buttons">
        <?php if ($isLoggedIn): ?>
            <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
            <a href="quests.php" class="btn btn-secondary">Browse Quests</a>
        <?php else: ?>
            <a href="register.php" class="btn btn-primary">Join the Guild</a>
            <a href="login.php" class="btn btn-secondary">Login</a>
        <?php endif; ?>
    </div>
</section>

<?php if (!empty($welcomeMsg)): ?>
    <div class="container">
        <div class="alert alert-success"><?php echo htmlspecialchars($welcomeMsg); ?></div>
    </div>
<?php endif; ?>

<div class="container">
    <div class="grid-3">
        <div class="stat-card">
            <div class="stat-icon" style="font-size: 2rem; margin-bottom: 10px;">&#9876;</div>
            <h3 class="card-title">Quest Board</h3>
            <p>Browse available quests posted by the Guild Masters. Find your next adventure and earn glory and rewards.</p>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="font-size: 2rem; margin-bottom: 10px;">&#128101;</div>
            <h3 class="card-title">Adventurer Profiles</h3>
            <p>View fellow adventurers, their classes, and levels. Join a party or recruit skilled companions.</p>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="font-size: 2rem; margin-bottom: 10px;">&#128200;</div>
            <h3 class="card-title">Guild Reports</h3>
            <p>Track guild statistics, quest completion rates, and adventurer rankings across the realm.</p>
        </div>
    </div>

    <div class="card" style="margin-top: 20px;">
        <h2 class="card-title">About the Guild</h2>
        <p>The Adventurer's Guild is a prestigious organization that connects brave souls with epic quests. Whether you are a seasoned warrior, a cunning rogue, or a wise wizard, the Guild welcomes all who seek fortune and glory.</p>
        <br>
        <p>Our Quest Board system allows Guild Masters to post missions of varying difficulty — from simple errands to legendary challenges. Adventurers can browse, accept, and track their quests, building their reputation and rising through the ranks.</p>
        <br>
        <a href="about.php" class="btn btn-primary">Learn More</a>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
