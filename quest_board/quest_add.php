<?php
/**
 * Add New Quest Page
 * Guild Masters can create new quests.
 */
require_once __DIR__ . '/includes/auth_check.php';

// Only Guild Masters can add quests
if ($_SESSION['role'] !== 'guild_master') {
    header("Location: dashboard.php");
    exit();
}

$pageTitle = "Post New Quest";
require_once __DIR__ . '/includes/functions.php';

$errors = array();
$successMessage = "";

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $difficulty = sanitizeInput($_POST['difficulty'] ?? '');
    $reward = sanitizeInput($_POST['reward'] ?? '');
    $status = sanitizeInput($_POST['status'] ?? 'OPEN');
    $location = sanitizeInput($_POST['location'] ?? '');
    $assignedTo = !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : NULL;
    
    // Validation
    if (empty($title)) {
        $errors[] = "Quest title is required.";
    } elseif (strlen($title) > 200) {
        $errors[] = "Title must not exceed 200 characters.";
    }
    
    if (empty($description)) {
        $errors[] = "Quest description is required.";
    }
    
    if (empty($difficulty)) {
        $errors[] = "Difficulty level is required.";
    } elseif (!in_array($difficulty, array('EASY', 'MEDIUM', 'HARD', 'LEGENDARY'))) {
        $errors[] = "Invalid difficulty level.";
    }
    
    if (empty($reward)) {
        $errors[] = "Reward is required.";
    }
    
    if (empty($errors)) {
        $conn = getConnection();
        
        $stmt = $conn->prepare("INSERT INTO quests (title, description, difficulty, reward, status, location, assigned_to, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssii", $title, $description, $difficulty, $reward, $status, $location, $assignedTo, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $successMessage = "Quest posted successfully!";
        } else {
            $errors[] = "Failed to post quest. Please try again.";
        }
        
        $stmt->close();
        closeConnection($conn);
    }
}

// Get list of adventurers for assignment
$conn = getConnection();
$adventurers = getAllAdventurers($conn);
closeConnection($conn);
?>

<?php include_once __DIR__ . '/includes/header.php'; ?>

<div class="container">
    <a href="quests.php" class="back-link">&larr; Back to Quest Board</a>
    
    <h1 class="page-title">Post New Quest</h1>
    <p class="page-subtitle">Create a new adventure for brave souls</p>
    
    <div class="form-container">
        <div class="card">
            <?php if (!empty($successMessage)): ?>
                <?php displayFlashMessage($successMessage, 'success'); ?>
                <p style="text-align: center; margin-top: 15px;">
                    <a href="quests.php" class="btn btn-primary">View Quest Board</a>
                    <a href="quest_add.php" class="btn btn-secondary" style="margin-left: 10px;">Post Another Quest</a>
                </p>
            <?php else: ?>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <strong>Errors:</strong><br>
                        <?php foreach ($errors as $error): ?>
                            &bull; <?php echo htmlspecialchars($error); ?><br>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="quest_add.php">
                    <div class="form-group">
                        <label for="title">Quest Title *</label>
                        <input type="text" id="title" name="title" required maxlength="200"
                               value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
                               placeholder="e.g., Slay the Dragon of Mount Doom">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description *</label>
                        <textarea id="description" name="description" required placeholder="Describe the quest details, objectives, and requirements..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="difficulty">Difficulty Level *</label>
                        <select id="difficulty" name="difficulty" required>
                            <option value="">-- Select Difficulty --</option>
                            <option value="EASY" <?php echo (isset($_POST['difficulty']) && $_POST['difficulty'] === 'EASY') ? 'selected' : ''; ?>>Easy</option>
                            <option value="MEDIUM" <?php echo (isset($_POST['difficulty']) && $_POST['difficulty'] === 'MEDIUM') ? 'selected' : ''; ?>>Medium</option>
                            <option value="HARD" <?php echo (isset($_POST['difficulty']) && $_POST['difficulty'] === 'HARD') ? 'selected' : ''; ?>>Hard</option>
                            <option value="LEGENDARY" <?php echo (isset($_POST['difficulty']) && $_POST['difficulty'] === 'LEGENDARY') ? 'selected' : ''; ?>>Legendary</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="reward">Reward *</label>
                        <input type="text" id="reward" name="reward" required maxlength="100"
                               value="<?php echo isset($_POST['reward']) ? htmlspecialchars($_POST['reward']) : ''; ?>"
                               placeholder="e.g., 500 Gold Coins, Rare Sword">
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" maxlength="150"
                               value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>"
                               placeholder="e.g., The Dark Forest">
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="OPEN" <?php echo (isset($_POST['status']) && $_POST['status'] === 'OPEN') ? 'selected' : ''; ?>>Open</option>
                            <option value="IN_PROGRESS" <?php echo (isset($_POST['status']) && $_POST['status'] === 'IN_PROGRESS') ? 'selected' : ''; ?>>In Progress</option>
                            <option value="COMPLETED" <?php echo (isset($_POST['status']) && $_POST['status'] === 'COMPLETED') ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="assigned_to">Assign to Adventurer</label>
                        <select id="assigned_to" name="assigned_to">
                            <option value="">-- Not Assigned --</option>
                            <?php foreach ($adventurers as $adv): ?>
                                <option value="<?php echo $adv['user_id']; ?>" 
                                        <?php echo (isset($_POST['assigned_to']) && $_POST['assigned_to'] == $adv['user_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($adv['full_name']); ?> (<?php echo htmlspecialchars($adv['class'] ?? 'Unknown'); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div style="text-align: center; margin-top: 25px;">
                        <button type="submit" class="btn btn-primary">Post Quest</button>
                        <a href="quests.php" class="btn btn-secondary" style="margin-left: 10px;">Cancel</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
