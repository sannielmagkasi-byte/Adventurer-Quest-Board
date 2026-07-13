<?php
/**
 * Application Review Page
 * Guild Masters review and approve/reject quest applications.
 */
require_once __DIR__ . '/includes/auth_check.php';
$pageTitle = "Review Application";
require_once __DIR__ . '/includes/functions.php';

// Only Guild Masters can review applications
if ($_SESSION['role'] !== 'guild_master') {
    header("Location: dashboard.php");
    exit();
}

$conn = getConnection();

$successMsg = "";
$errorMsg = "";

if (isset($_GET['id']) && isset($_GET['action'])) {
    $appId = intval($_GET['id']);
    $action = sanitizeInput($_GET['action']);
    
    // Get application details
    $stmt = $conn->prepare("SELECT a.*, u.full_name AS applicant_name, u.class, u.level, u.email AS applicant_email, q.title AS quest_title, q.description AS quest_description, q.difficulty AS quest_difficulty, q.reward AS quest_reward FROM quest_applications a JOIN users u ON a.applicant_id = u.user_id JOIN quests q ON a.quest_id = q.quest_id WHERE a.app_id = ?");
    $stmt->bind_param("i", $appId);
    $stmt->execute();
    $app = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($app) {
        if ($app['status'] !== 'PENDING') {
            $errorMsg = "This application has already been reviewed.";
        } elseif ($action === 'approve') {
            if (updateApplicationStatus($conn, $appId, 'APPROVED')) {
                // Assign quest to applicant
                $stmt2 = $conn->prepare("UPDATE quests SET assigned_to = ?, status = 'IN_PROGRESS' WHERE quest_id = ?");
                $stmt2->bind_param("ii", $app['applicant_id'], $app['quest_id']);
                $stmt2->execute();
                $stmt2->close();
                
                // Auto-reject other pending applications for the same quest
                $stmt3 = $conn->prepare("UPDATE quest_applications SET status = 'REJECTED', reviewed_at = NOW() WHERE quest_id = ? AND app_id != ? AND status = 'PENDING'");
                $stmt3->bind_param("ii", $app['quest_id'], $appId);
                $stmt3->execute();
                $stmt3->close();
                
                $successMsg = "Application approved! Quest assigned to " . htmlspecialchars($app['applicant_name']) . ".";
            } else {
                $errorMsg = "Failed to update application status.";
            }
        } elseif ($action === 'reject') {
            if (updateApplicationStatus($conn, $appId, 'REJECTED')) {
                $successMsg = "Application rejected.";
            } else {
                $errorMsg = "Failed to update application status.";
            }
        } else {
            $errorMsg = "Invalid action.";
        }
    } else {
        $errorMsg = "Application not found.";
    }
} else {
    // Show all pending applications if no specific action
    $appId = null;
}

// Refresh application data if available
if (isset($app) && !empty($app)) {
    // Re-fetch to get updated status
    $stmt = $conn->prepare("SELECT a.*, u.full_name AS applicant_name, u.class, u.level, u.email AS applicant_email, u.guild_id, q.title AS quest_title, q.description AS quest_description, q.difficulty AS quest_difficulty, q.reward AS quest_reward, q.status AS quest_status FROM quest_applications a JOIN users u ON a.applicant_id = u.user_id JOIN quests q ON a.quest_id = q.quest_id WHERE a.app_id = ?");
    $stmt->bind_param("i", $appId);
    $stmt->execute();
    $app = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // Get user guild name
    if ($app && $app['guild_id']) {
        $appGuild = getGuildById($conn, $app['guild_id']);
    }
}

// Also get all pending applications for this GM to manage
$pendingApps = array();
$stmt = $conn->prepare("SELECT a.*, u.full_name AS applicant_name, u.class, u.level, q.title AS quest_title, q.difficulty FROM quest_applications a JOIN users u ON a.applicant_id = u.user_id JOIN quests q ON a.quest_id = q.quest_id WHERE a.status = 'PENDING' ORDER BY a.applied_at DESC");
$stmt->execute();
$pendingApps = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

closeConnection($conn);
?>

<?php include_once __DIR__ . '/includes/header.php'; ?>

<div class="container">
    <a href="dashboard.php" class="back-link">&larr; Back to Dashboard</a>
    
    <h1 class="page-title">Quest Application Review</h1>
    <p class="page-subtitle">Review and manage adventurer quest applications</p>
    
    <?php if (!empty($successMsg)): ?>
        <div class="alert alert-success"><?php echo $successMsg; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($errorMsg)): ?>
        <div class="alert alert-error"><?php echo $errorMsg; ?></div>
    <?php endif; ?>
    
    <?php if (isset($app) && !empty($app)): ?>
        <!-- Individual Application Detail -->
        <div class="card" style="margin-bottom: 20px;">
            <h2 class="card-title">Application Details</h2>
            
            <div style="margin-top: 15px;">
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px; background: rgba(200, 176, 97, 0.1); border-radius: 8px; margin-bottom: 15px;">
                    <div>
                        <p style="margin-bottom: 5px;"><strong>Quest:</strong> <?php echo htmlspecialchars($app['quest_title']); ?></p>
                        <p style="margin-bottom: 5px;"><strong>Difficulty:</strong> <?php echo getDifficultyBadge($app['quest_difficulty']); ?></p>
                        <p style="margin-bottom: 5px;"><strong>Reward:</strong> <?php echo htmlspecialchars($app['quest_reward']); ?></p>
                    </div>
                    <div>
                        <?php echo getApplicationStatusBadge($app['status']); ?>
                    </div>
                </div>
                
                <div style="padding: 15px; background: rgba(100, 100, 100, 0.1); border-radius: 8px;">
                    <h3 style="color: var(--color-gold); margin-bottom: 10px;">Applicant: <?php echo htmlspecialchars($app['applicant_name']); ?></h3>
                    <p><strong>Class:</strong> <?php echo htmlspecialchars($app['class'] ?? 'N/A'); ?></p>
                    <p><strong>Level:</strong> <?php echo $app['level']; ?></p>
                    <p><strong>Guild:</strong> <?php echo isset($appGuild) ? htmlspecialchars($appGuild['guild_name']) : 'None'; ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($app['applicant_email']); ?></p>
                    <p><strong>Applied:</strong> <?php echo formatDate($app['applied_at']); ?></p>
                    <?php if ($app['reviewed_at']): ?>
                        <p><strong>Reviewed:</strong> <?php echo formatDate($app['reviewed_at']); ?></p>
                    <?php endif; ?>
                </div>
                
                <?php if ($app['status'] === 'PENDING'): ?>
                    <div style="margin-top: 20px; text-align: center;">
                        <a href="application_review.php?id=<?php echo $app['app_id']; ?>&action=approve" class="btn btn-success" onclick="return confirm('Approve this application? The quest will be assigned to this adventurer and other pending applications will be rejected.');">Approve & Assign</a>
                        <a href="application_review.php?id=<?php echo $app['app_id']; ?>&action=reject" class="btn btn-danger" onclick="return confirm('Reject this application?');" style="margin-left: 10px;">Reject</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- All Pending Applications -->
    <div class="card">
        <h2 class="card-title">All Pending Applications (<?php echo count($pendingApps); ?>)</h2>
        
        <?php if (!empty($pendingApps)): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Adventurer</th>
                            <th>Class</th>
                            <th>Level</th>
                            <th>Quest</th>
                            <th>Difficulty</th>
                            <th>Applied</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingApps as $pa): ?>
                            <tr>
                                <td>
                                    <a href="profile.php?id=<?php echo $pa['applicant_id']; ?>" class="action-link">
                                        <strong><?php echo htmlspecialchars($pa['applicant_name']); ?></strong>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($pa['class'] ?? 'N/A'); ?></td>
                                <td><?php echo $pa['level']; ?></td>
                                <td><?php echo htmlspecialchars($pa['quest_title']); ?></td>
                                <td><?php echo getDifficultyBadge($pa['difficulty']); ?></td>
                                <td><?php echo formatDate($pa['applied_at']); ?></td>
                                <td>
                                    <a href="application_review.php?id=<?php echo $pa['app_id']; ?>&action=approve" class="btn btn-success btn-sm" onclick="return confirm('Approve this application?');">Approve</a>
                                    <a href="application_review.php?id=<?php echo $pa['app_id']; ?>&action=reject" class="btn btn-danger btn-sm" onclick="return confirm('Reject this application?');">Reject</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state" style="padding: 30px;">
                <p>No pending applications to review.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Application History -->
    <div class="card" style="margin-top: 20px;">
        <h2 class="card-title">Recent Application History</h2>
        
        <?php
        $result = $conn->query("SELECT a.*, u.full_name AS applicant_name, q.title AS quest_title FROM quest_applications a JOIN users u ON a.applicant_id = u.user_id JOIN quests q ON a.quest_id = q.quest_id WHERE a.status != 'PENDING' ORDER BY a.reviewed_at DESC LIMIT 10");
        $history = $result->fetch_all(MYSQLI_ASSOC);
        ?>
        
        <?php if (!empty($history)): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Adventurer</th>
                            <th>Quest</th>
                            <th>Status</th>
                            <th>Applied</th>
                            <th>Reviewed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $h): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($h['applicant_name']); ?></td>
                                <td><?php echo htmlspecialchars($h['quest_title']); ?></td>
                                <td><?php echo getApplicationStatusBadge($h['status']); ?></td>
                                <td><?php echo formatDate($h['applied_at']); ?></td>
                                <td><?php echo $h['reviewed_at'] ? formatDate($h['reviewed_at']) : 'N/A'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="color: var(--color-text-muted);">No application history yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
