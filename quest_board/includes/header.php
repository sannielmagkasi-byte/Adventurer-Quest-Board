<?php
/**
 * HTML Header Template
 * Included at the top of every page for consistent layout.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/functions.php';

$isLoggedIn = isset($_SESSION['user_id']);
$pageTitle = isset($pageTitle) ? $pageTitle . " — Quest Board" : "Quest Board";

// Get wallet balance for logged-in users
$walletBalance = null;
if ($isLoggedIn) {
    $conn = getConnection();
    $wallet = getWalletBalance($conn, $_SESSION['user_id']);
    $walletBalance = $wallet['balance'] ?? 0;
    closeConnection($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="logo">
                    <span class="logo-icon">&#9876;</span>
                    <span class="logo-text">Adventurer's Guild</span>
                </a>
                <nav class="main-nav">
                    <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">Home</a>
                    <a href="about.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'about.php' ? 'active' : ''; ?>">About</a>
                    
                    <?php if ($isLoggedIn): ?>
                        <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a>
                        <a href="quests.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'quests.php' || basename($_SERVER['PHP_SELF']) === 'quest_add.php' || basename($_SERVER['PHP_SELF']) === 'quest_edit.php' ? 'active' : ''; ?>">Quests</a>
                        <a href="adventurers.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'adventurers.php' ? 'active' : ''; ?>">Adventurers</a>
                        <a href="guilds.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'guilds.php' || basename($_SERVER['PHP_SELF']) === 'guild_detail.php' ? 'active' : ''; ?>">Guilds</a>
                        <a href="bank.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'bank.php' ? 'active' : ''; ?>">&#128176; Bank</a>
                        <a href="reports.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : ''; ?>">Reports</a>
                        <?php
                        $userAvatar = getUserAvatar($_SESSION['user_id']);
                        ?>
                        <a href="profile.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>">
                            <?php if ($userAvatar): ?>
                                <img src="uploads/avatars/<?php echo htmlspecialchars($userAvatar); ?>" alt="Avatar" style="width: 28px; height: 28px; border-radius: 50%; object-fit: cover; margin-right: 5px; vertical-align: middle;">
                            <?php endif; ?>
                            <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                        </a>
                        <div class="wallet-display" title="Gold Balance">
                            &#128176; <?php echo formatGold($walletBalance); ?>
                        </div>
                        <a href="logout.php" class="nav-link btn-logout">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'login.php' ? 'active' : ''; ?>">Login</a>
                        <a href="register.php" class="nav-link btn-register <?php echo basename($_SERVER['PHP_SELF']) === 'register.php' ? 'active' : ''; ?>">Register</a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>
    
    <main class="main-content">
