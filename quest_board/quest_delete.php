<?php
/**
 * Delete Quest Page
 * Guild Masters can delete quests.
 */
require_once __DIR__ . '/includes/auth_check.php';

// Only Guild Masters can delete quests
if ($_SESSION['role'] !== 'guild_master') {
    header("Location: dashboard.php");
    exit();
}

require_once __DIR__ . '/includes/functions.php';

// Get quest ID from URL
$questId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($questId <= 0) {
    header("Location: quests.php");
    exit();
}

$conn = getConnection();
$quest = getQuestById($conn, $questId);
closeConnection($conn);

if (!$quest) {
    header("Location: quests.php");
    exit();
}

// Verify the quest was created by the current user
if ($quest['created_by'] != $_SESSION['user_id']) {
    header("Location: quests.php");
    exit();
}

// Process deletion on GET (confirmed via URL)
$conn = getConnection();
$stmt = $conn->prepare("DELETE FROM quests WHERE quest_id = ? AND created_by = ?");
$stmt->bind_param("ii", $questId, $_SESSION['user_id']);

if ($stmt->execute()) {
    $successMessage = "Quest \"" . htmlspecialchars($quest['title']) . "\" has been deleted.";
} else {
    $errorMessage = "Failed to delete quest.";
}

$stmt->close();
closeConnection($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quest Deleted — Quest Board</title>
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
                    <a href="dashboard.php" class="nav-link">Dashboard</a>
                    <a href="quests.php" class="nav-link active">Quests</a>
                </nav>
            </div>
        </div>
    </header>
    
    <main class="main-content">
        <div class="container">
            <h1 class="page-title">Quest Deleted</h1>
            
            <div class="form-container">
                <div class="card">
                    <?php if (isset($successMessage)): ?>
                        <div class="alert alert-success">
                            <?php echo $successMessage; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-error">
                            <?php echo isset($errorMessage) ? $errorMessage : "An error occurred."; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="quests.php" class="btn btn-primary">Back to Quest Board</a>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <footer class="main-footer">
        <div class="container">
            <p>&copy; <?php echo date("Y"); ?> Adventurer's Guild — Quest Board System</p>
            <p class="footer-note">A CCS0043 Final Project | FEU Institute of Technology</p>
        </div>
    </footer>
</body>
</html>
