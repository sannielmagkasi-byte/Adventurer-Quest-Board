<?php
/**
 * Quests List Page
 * Display all quests with filtering by status and difficulty.
 * Adventurers can apply for quests. Guild Masters can manage applications.
 */
require_once __DIR__ . '/includes/auth_check.php';
$pageTitle = "Quest Board";
require_once __DIR__ . '/includes/functions.php';

$conn = getConnection();

// Handle filter parameters
$statusFilter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$difficultyFilter = isset($_GET['difficulty']) ? sanitizeInput($_GET['difficulty']) : '';

// Handle apply action
$applyMsg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_quest_id'])) {
    $questId = intval($_POST['apply_quest_id']);
    $result = createQuestApplication($conn, $questId, $_SESSION['user_id']);
    if ($result === 'duplicate') {
        $applyMsg = "You have already applied for this quest.";
    } elseif ($result) {
        $applyMsg = "Application submitted successfully!";
    } else {
        $applyMsg = "Failed to submit application.";
    }
}

// Build query with filters
$sql = "SELECT q.*, u.full_name AS assigned_to_name, u2.full_name AS created_by_name FROM quests q 
        LEFT JOIN users u ON q.assigned_to = u.user_id 
        LEFT JOIN users u2 ON q.created_by = u2.user_id 
        WHERE 1=1";

$params = array();
$types = "";

if (!empty($statusFilter)) {
    $sql .= " AND q.status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

if (!empty($difficultyFilter)) {
    $sql .= " AND q.difficulty = ?";
    $params[] = $difficultyFilter;
    $types .= "s";
}

$sql .= " ORDER BY q.created_at DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $quests = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $result = $conn->query($sql);
    $quests = $result->fetch_all(MYSQLI_ASSOC);
}

// For non-GM users, get list of quests they've already applied to
$appliedQuestIds = array();
if ($_SESSION['role'] !== 'guild_master') {
    $stmt = $conn->prepare("SELECT quest_id FROM quest_applications WHERE applicant_id = ? AND status IN ('PENDING', 'APPROVED')");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $appResult = $stmt->get_result();
    while ($row = $appResult->fetch_assoc()) {
        $appliedQuestIds[] = $row['quest_id'];
    }
    $stmt->close();
}

closeConnection($conn);
?>

<?php include_once __DIR__ . '/includes/header.php'; ?>

<div class="container">
    <h1 class="page-title">Quest Board</h1>
    <p class="page-subtitle">Browse available quests and find your next adventure</p>
    
    <?php if (!empty($applyMsg)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($applyMsg); ?></div>
    <?php endif; ?>
    
    <!-- Filter Bar -->
    <div class="card">
        <form method="GET" action="quests.php" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 150px;">
                <label for="status_filter">Status</label>
                <select id="status_filter" name="status">
                    <option value="">All Statuses</option>
                    <option value="OPEN" <?php echo $statusFilter === 'OPEN' ? 'selected' : ''; ?>>Open</option>
                    <option value="IN_PROGRESS" <?php echo $statusFilter === 'IN_PROGRESS' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="COMPLETED" <?php echo $statusFilter === 'COMPLETED' ? 'selected' : ''; ?>>Completed</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 150px;">
                <label for="difficulty_filter">Difficulty</label>
                <select id="difficulty_filter" name="difficulty">
                    <option value="">All Difficulties</option>
                    <option value="EASY" <?php echo $difficultyFilter === 'EASY' ? 'selected' : ''; ?>>Easy</option>
                    <option value="MEDIUM" <?php echo $difficultyFilter === 'MEDIUM' ? 'selected' : ''; ?>>Medium</option>
                    <option value="HARD" <?php echo $difficultyFilter === 'HARD' ? 'selected' : ''; ?>>Hard</option>
                    <option value="LEGENDARY" <?php echo $difficultyFilter === 'LEGENDARY' ? 'selected' : ''; ?>>Legendary</option>
                </select>
            </div>
            <div style="padding-top: 20px;">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <a href="quests.php" class="btn btn-secondary btn-sm" style="margin-left: 5px;">Clear</a>
            </div>
        </form>
    </div>
    
    <!-- Action Bar -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin: 20px 0; flex-wrap: wrap; gap: 10px;">
        <p style="color: var(--color-text-muted);"><?php echo count($quests); ?> quest(s) found</p>
        <?php if ($_SESSION['role'] === 'guild_master'): ?>
            <a href="quest_add.php" class="btn btn-success">+ Post New Quest</a>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($quests)): ?>
        <?php foreach ($quests as $quest): ?>
            <div class="quest-card">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px;">
                    <h3><?php echo htmlspecialchars($quest['title']); ?></h3>
                    <div>
                        <?php echo getDifficultyBadge($quest['difficulty']); ?>
                        <?php echo getStatusBadge($quest['status']); ?>
                    </div>
                </div>
                
                <div class="quest-meta">
                    <span>&#127758; <?php echo htmlspecialchars($quest['location'] ?? 'Unknown Location'); ?></span>
                    <span>&#128176; <?php echo htmlspecialchars($quest['reward']); ?></span>
                    <span>&#128279; By <?php echo htmlspecialchars($quest['created_by_name'] ?? 'Unknown'); ?></span>
                    <?php if (!empty($quest['assigned_to_name'])): ?>
                        <span>&#128101; Assigned to: <?php echo htmlspecialchars($quest['assigned_to_name']); ?></span>
                    <?php endif; ?>
                    <span>&#128197; <?php echo formatDate($quest['created_at']); ?></span>
                </div>
                
                <div class="quest-description">
                    <?php echo nl2br(htmlspecialchars($quest['description'])); ?>
                </div>
                
                <div class="quest-actions">
                    <?php if ($_SESSION['role'] === 'guild_master'): ?>
                        <a href="quest_edit.php?id=<?php echo $quest['quest_id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                        <a href="quest_delete.php?id=<?php echo $quest['quest_id']; ?>" class="btn btn-danger btn-sm" 
                           onclick="return confirm('Are you sure you want to delete this quest? This action cannot be undone.');">Delete</a>
                        <a href="guilds.php?action=applications&quest_id=<?php echo $quest['quest_id']; ?>" class="btn btn-secondary btn-sm">View Applications</a>
                    <?php else: ?>
                        <?php if ($quest['status'] === 'OPEN' && !in_array($quest['quest_id'], $appliedQuestIds) && $quest['assigned_to'] != $_SESSION['user_id']): ?>
                            <form method="POST" action="quests.php" style="display: inline;">
                                <input type="hidden" name="apply_quest_id" value="<?php echo $quest['quest_id']; ?>">
                                <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Apply for this quest?')">Apply for Quest</button>
                            </form>
                        <?php elseif (in_array($quest['quest_id'], $appliedQuestIds)): ?>
                            <span class="btn btn-secondary btn-sm" style="cursor: default;">Applied</span>
                        <?php elseif ($quest['assigned_to'] == $_SESSION['user_id']): ?>
                            <span class="btn btn-success btn-sm" style="cursor: default;">Assigned to You</span>
                        <?php elseif ($quest['status'] !== 'OPEN'): ?>
                            <span class="btn btn-secondary btn-sm" style="cursor: default;">Closed</span>
                        <?php else: ?>
                            <span class="btn btn-secondary btn-sm" style="cursor: default;">Unavailable</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="card empty-state">
            <div class="empty-icon">&#128220;</div>
            <p>No quests found matching your filters.</p>
            <a href="quests.php" class="btn btn-primary btn-sm" style="margin-top: 15px;">Clear Filters</a>
        </div>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
