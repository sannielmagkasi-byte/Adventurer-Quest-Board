<?php
/**
 * Edit Quest Page
 * Guild Masters can update existing quests.
 */
require_once __DIR__ . '/includes/auth_check.php';

// Only Guild Masters can edit quests
if ($_SESSION['role'] !== 'guild_master') {
    header("Location: dashboard.php");
    exit();
}

$pageTitle = "Edit Quest";
require_once __DIR__ . '/includes/functions.php';

// Get quest ID from URL
$questId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($questId <= 0) {
    header("Location: quests.php");
    exit();
}

$conn = getConnection();
$quest = getQuestById($conn, $questId);
$adventurers = getAllAdventurers($conn);
closeConnection($conn);

if (!$quest) {
    header("Location: quests.php");
    exit();
}

$errors = array();
$successMessage = "";

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $difficulty = sanitizeInput($_POST['difficulty'] ?? '');
    $reward = sanitizeInput($_POST['reward'] ?? '');
    $status = sanitizeInput($_POST['status'] ?? '');
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
        
        $stmt = $conn->prepare("UPDATE quests SET title = ?, description = ?, difficulty = ?, reward = ?, status = ?, location = ?, assigned_to = ? WHERE quest_id = ? AND created_by = ?");
        $stmt->bind_param("ssssssiis", $title, $description, $difficulty, $reward, $status, $location, $assignedTo, $questId, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0 || $stmt->errno === 0) {
                $successMessage = "Quest updated successfully!";
                // Refresh quest data
                $quest = getQuestById($conn, $questId);
            } else {
                $errors[] = "No changes were made.";
            }
        } else {
            $errors[] = "Failed to update quest. Please try again.";
        }
        
        $stmt->close();
        closeConnection($conn);
    }
}
?>

<?php include_once __DIR__ . '/includes/header.php'; ?>

<div class="container">
    <a href="quests.php" class="back-link">&larr; Back to Quest Board</a>
    
    <h1 class="page-title">Edit Quest</h1>
    <p class="page-subtitle">Update quest details</p>
    
    <div class="form-container">
        <div class="card">
            <?php if (!empty($successMessage)): ?>
                <?php displayFlashMessage($successMessage, 'success'); ?>
                <p style="text-align: center; margin-top: 15px;">
                    <a href="quests.php" class="btn btn-primary">View Quest Board</a>
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
                
                <form method="POST" action="quest_edit.php?id=<?php echo $questId; ?>">
                    <div class="form-group">
                        <label for="title">Quest Title *</label>
                        <input type="text" id="title" name="title" required maxlength="200"
                               value="<?php echo htmlspecialchars($quest['title']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description *</label>
                        <textarea id="description" name="description" required><?php echo htmlspecialchars($quest['description']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="difficulty">Difficulty Level *</label>
                        <select id="difficulty" name="difficulty" required>
                            <option value="EASY" <?php echo $quest['difficulty'] === 'EASY' ? 'selected' : ''; ?>>Easy</option>
                            <option value="MEDIUM" <?php echo $quest['difficulty'] === 'MEDIUM' ? 'selected' : ''; ?>>Medium</option>
                            <option value="HARD" <?php echo $quest['difficulty'] === 'HARD' ? 'selected' : ''; ?>>Hard</option>
                            <option value="LEGENDARY" <?php echo $quest['difficulty'] === 'LEGENDARY' ? 'selected' : ''; ?>>Legendary</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="reward">Reward *</label>
                        <input type="text" id="reward" name="reward" required maxlength="100"
                               value="<?php echo htmlspecialchars($quest['reward']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" maxlength="150"
                               value="<?php echo htmlspecialchars($quest['location'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="OPEN" <?php echo $quest['status'] === 'OPEN' ? 'selected' : ''; ?>>Open</option>
                            <option value="IN_PROGRESS" <?php echo $quest['status'] === 'IN_PROGRESS' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="COMPLETED" <?php echo $quest['status'] === 'COMPLETED' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="assigned_to">Assign to Adventurer</label>
                        <select id="assigned_to" name="assigned_to">
                            <option value="">-- Not Assigned --</option>
                            <?php foreach ($adventurers as $adv): ?>
                                <option value="<?php echo $adv['user_id']; ?>" 
                                        <?php echo $quest['assigned_to'] == $adv['user_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($adv['full_name']); ?> (<?php echo htmlspecialchars($adv['class'] ?? 'Unknown'); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div style="text-align: center; margin-top: 25px;">
                        <button type="submit" class="btn btn-primary">Update Quest</button>
                        <a href="quests.php" class="btn btn-secondary" style="margin-left: 10px;">Cancel</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
