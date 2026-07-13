<?php
/**
 * Guilds Page
 * Display all guilds and their members.
 * Guild Masters can manage quest applications from here.
 */
require_once __DIR__ . '/includes/auth_check.php';
$pageTitle = "Guilds";
require_once __DIR__ . '/includes/functions.php';

$conn = getConnection();

// Handle quest application review (Guild Master only)
if ($_SESSION['role'] === 'guild_master' && isset($_GET['action']) && isset($_GET['id'])) {
    $appId = intval($_GET['id']);
    $action = sanitizeInput($_GET['action']);
    
    if ($action === 'approve') {
        if (updateApplicationStatus($conn, $appId, 'APPROVED')) {
            // When approved, also assign the quest to the applicant
            $stmt = $conn->prepare("SELECT quest_id, applicant_id FROM quest_applications WHERE app_id = ?");
            $stmt->bind_param("i", $appId);
            $stmt->execute();
            $appData = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if ($appData) {
                // Assign quest to the applicant
                $stmt2 = $conn->prepare("UPDATE quests SET assigned_to = ?, status = 'IN_PROGRESS' WHERE quest_id = ?");
                $stmt2->bind_param("ii", $appData['applicant_id'], $appData['quest_id']);
                $stmt2->execute();
                $stmt2->close();
                
                // Auto-reject all other pending applications for the same quest
                $stmt3 = $conn->prepare("UPDATE quest_applications SET status = 'REJECTED', reviewed_at = NOW() WHERE quest_id = ? AND app_id != ? AND status = 'PENDING'");
                $stmt3->bind_param("ii", $appData['quest_id'], $appId);
                $stmt3->execute();
                $stmt3->close();
            }
            $successMsg = "Application approved! Quest assigned to the adventurer.";
        }
    } elseif ($action === 'reject') {
        if (updateApplicationStatus($conn, $appId, 'REJECTED')) {
            $successMsg = "Application rejected.";
        }
    }
}

// Get all guilds with member counts
$result = $conn->query("
    SELECT g.*, COUNT(u.user_id) AS member_count 
    FROM guilds g 
    LEFT JOIN users u ON g.guild_id = u.guild_id 
    GROUP BY g.guild_id 
    ORDER BY g.guild_name ASC
");
$guilds = $result->fetch_all(MYSQLI_ASSOC);

// If viewing a specific guild's detail
$selectedGuild = null;
$guildMembers = array();
$guildQuests = array();
if (isset($_GET['id'])) {
    $guildId = intval($_GET['id']);
    $selectedGuild = getGuildById($conn, $guildId);
    if ($selectedGuild) {
        $guildMembers = getGuildMembers($conn, $guildId);
        
        // Get quests relevant to this guild
        $stmt = $conn->prepare("SELECT q.*, u.full_name AS created_by_name, u2.full_name AS assigned_to_name FROM quests q LEFT JOIN users u ON q.created_by = u.user_id LEFT JOIN users u2 ON q.assigned_to = u2.user_id WHERE q.created_by IN (SELECT user_id FROM users WHERE guild_id = ?) OR q.assigned_to IN (SELECT user_id FROM users WHERE guild_id = ?) ORDER BY q.created_at DESC");
        $stmt->bind_param("ii", $guildId, $guildId);
        $stmt->execute();
        $guildQuests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

// For Guild Masters: get pending applications for a specific quest
$questApplications = array();
if ($_SESSION['role'] === 'guild_master' && isset($_GET['quest_id'])) {
    $questApplications = getQuestApplications($conn, intval($_GET['quest_id']));
}

closeConnection($conn);
?>

<?php include_once __DIR__ . '/includes/header.php'; ?>

<div class="container">
    <h1 class="page-title">Guild Directory</h1>
    <p class="page-subtitle">Explore the guilds of the realm</p>
    
    <?php if (!empty($successMsg)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($successMsg); ?></div>
    <?php endif; ?>
    
    <!-- Guild Applications Management (Guild Master only) -->
    <?php if ($_SESSION['role'] === 'guild_master' && !empty($questApplications)): ?>
        <div class="card" style="margin-bottom: 20px;">
            <h2 class="card-title">&#128203; Quest Applications</h2>
            <p style="color: var(--color-text-muted); margin-bottom: 15px;">Applications for quest: <strong><?php echo htmlspecialchars($questApplications[0]['quest_title'] ?? ''); ?></strong></p>
            
            <?php if (!empty($questApplications)): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Adventurer</th>
                                <th>Class</th>
                                <th>Level</th>
                                <th>Status</th>
                                <th>Applied</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($questApplications as $app): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($app['full_name']); ?></strong> (@<?php echo htmlspecialchars($app['username']); ?>)</td>
                                    <td><?php echo htmlspecialchars($app['class'] ?? 'N/A'); ?></td>
                                    <td><?php echo $app['level']; ?></td>
                                    <td><?php echo getApplicationStatusBadge($app['status']); ?></td>
                                    <td><?php echo formatDate($app['applied_at']); ?></td>
                                    <td>
                                        <?php if ($app['status'] === 'PENDING'): ?>
                                            <a href="guilds.php?action=approve&id=<?php echo $app['app_id']; ?>" class="btn btn-success btn-sm" onclick="return confirm('Approve this application? The quest will be assigned to this adventurer.');">Approve</a>
                                            <a href="guilds.php?action=reject&id=<?php echo $app['app_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Reject this application?');">Reject</a>
                                        <?php elseif ($app['status'] === 'APPROVED'): ?>
                                            <span style="color: var(--color-success);">&#10003; Approved</span>
                                        <?php elseif ($app['status'] === 'REJECTED'): ?>
                                            <span style="color: var(--color-error);">&#10007; Rejected</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($selectedGuild): ?>
        <!-- Single Guild Detail View -->
        <a href="guilds.php" class="back-link">&larr; Back to Guilds</a>
        
        <div style="text-align: center; padding: 20px; margin-bottom: 20px; background: linear-gradient(135deg, rgba(200, 176, 97, 0.1), rgba(200, 176, 97, 0.05)); border: 1px solid var(--color-gold); border-radius: 8px;">
            <h2 style="color: var(--color-gold); font-size: 2rem; margin-bottom: 5px;"><?php echo htmlspecialchars($selectedGuild['guild_name']); ?></h2>
            <p style="color: var(--color-text-muted);"><?php echo htmlspecialchars($selectedGuild['guild_description'] ?? 'No description available.'); ?></p>
            <p style="color: var(--color-gold); margin-top: 10px;"><strong><?php echo count($guildMembers); ?> member(s)</strong></p>
        </div>
        
        <div class="grid-2">
            <!-- Guild Members -->
            <div class="card">
                <h2 class="card-title">Guild Members</h2>
                <?php if (!empty($guildMembers)): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Class</th>
                                    <th>Level</th>
                                    <th>Role</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($guildMembers as $member): ?>
                                    <tr>
                                        <td>
                                            <a href="profile.php?id=<?php echo $member['user_id']; ?>" class="action-link">
                                                <strong><?php echo htmlspecialchars($member['full_name']); ?></strong>
                                            </a>
                                            <br><small style="color: var(--color-text-muted);">@<?php echo htmlspecialchars($member['username']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($member['class'] ?? 'N/A'); ?></td>
                                        <td><?php echo $member['level']; ?></td>
                                        <td>
                                            <?php 
                                            if ($member['role'] === 'guild_master') {
                                                echo '<span class="badge badge-legendary">Guild Master</span>';
                                            } else {
                                                echo '<span class="badge badge-easy">Adventurer</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="color: var(--color-text-muted);">No members yet.</p>
                <?php endif; ?>
            </div>
            
            <!-- Guild Quests -->
            <div class="card">
                <h2 class="card-title">Guild Quests</h2>
                <?php if (!empty($guildQuests)): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Quest</th>
                                    <th>Difficulty</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($guildQuests as $quest): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($quest['title']); ?></strong>
                                            <br><small style="color: var(--color-text-muted);">By <?php echo htmlspecialchars($quest['created_by_name'] ?? 'Unknown'); ?></small>
                                            <?php if ($quest['assigned_to_name']): ?>
                                                <br><small style="color: var(--color-text-muted);">Assigned: <?php echo htmlspecialchars($quest['assigned_to_name']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo getDifficultyBadge($quest['difficulty']); ?></td>
                                        <td><?php echo getStatusBadge($quest['status']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="color: var(--color-text-muted);">No quests from this guild yet.</p>
                <?php endif; ?>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Guild Listing -->
        <?php if (!empty($guilds)): ?>
            <div class="grid-3">
                <?php foreach ($guilds as $guild): ?>
                    <div class="card" style="text-align: center;">
                        <div style="font-size: 3rem; margin-bottom: 10px;">&#9876;</div>
                        <h3 style="color: var(--color-gold); margin-bottom: 10px;"><?php echo htmlspecialchars($guild['guild_name']); ?></h3>
                        <p style="color: var(--color-text-muted); font-size: 0.9rem; margin-bottom: 15px;">
                            <?php echo htmlspecialchars(substr($guild['guild_description'] ?? 'No description available.', 0, 120)); ?>
                        </p>
                        <p style="margin-bottom: 15px;">
                            <strong style="color: var(--color-gold);"><?php echo $guild['member_count']; ?></strong> member(s)
                        </p>
                        <a href="guilds.php?id=<?php echo $guild['guild_id']; ?>" class="btn btn-primary btn-sm">View Guild</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="card empty-state">
                <div class="empty-icon">&#9876;</div>
                <p>No guilds have been created yet.</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
