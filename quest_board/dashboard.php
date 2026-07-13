<?php
/**
 * Dashboard / Main Page
 * Adventurer Guild Registration & Quest Board
 */
require_once __DIR__ . '/includes/auth_check.php';
$pageTitle = "Dashboard";
require_once __DIR__ . '/includes/functions.php';

$conn = getConnection();
$currentUser = getUserById($conn, $_SESSION['user_id']);
$stats = getQuestStatistics($conn);

// Get user guild info
$userGuild = null;
if ($currentUser['guild_id']) {
    $userGuild = getGuildById($conn, $currentUser['guild_id']);
}

// Get wallet balance
$wallet = getWalletBalance($conn, $_SESSION['user_id']);

// Get recent quests (last 5)
$result = $conn->query("SELECT q.*, u.full_name AS assigned_to_name FROM quests q LEFT JOIN users u ON q.assigned_to = u.user_id ORDER BY q.created_at DESC LIMIT 5");
$recentQuests = $result->fetch_all(MYSQLI_ASSOC);

// Get pending applications for Guild Masters
$pendingApps = array();
if ($_SESSION['role'] === 'guild_master') {
    $stmt = $conn->prepare("SELECT a.*, u.full_name AS applicant_name, u.class, u.level, q.title AS quest_title, q.difficulty FROM quest_applications a JOIN users u ON a.applicant_id = u.user_id JOIN quests q ON a.quest_id = q.quest_id WHERE a.status = 'PENDING' ORDER BY a.applied_at DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    $pendingApps = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Get user's pending applications
$myPendingApps = 0;
if ($_SESSION['role'] !== 'guild_master') {
    $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM quest_applications WHERE applicant_id = ? AND status = 'PENDING'");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $myPendingApps = $result->fetch_assoc()['count'];
    $stmt->close();
}

closeConnection($conn);
?>

<?php include_once __DIR__ . '/includes/header.php'; ?>

<div class="container">
    <h1 class="page-title">Dashboard</h1>
    <p class="page-subtitle"><?php echo htmlspecialchars($_SESSION['full_name']); ?> — Level <?php echo intval($_SESSION['level']); ?> <?php echo htmlspecialchars($_SESSION['class'] ?? 'Adventurer'); ?> <?php echo $userGuild ? '— ' . htmlspecialchars($userGuild['guild_name']) : ''; ?></p>
    
    <!-- Stats Overview -->
    <div class="grid-3" style="margin-bottom: 30px;">
        <div class="stat-card">
            <div class="stat-number"><?php echo $stats['total_quests']; ?></div>
            <div class="stat-label">Total Quests</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $stats['total_adventurers']; ?></div>
            <div class="stat-label">Registered Adventurers</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" style="color: var(--color-gold);">&#128176; <?php echo formatGold($wallet['balance'] ?? 0); ?></div>
            <div class="stat-label">Your Gold Balance</div>
        </div>
    </div>
    
    <!-- Notifications -->
    <?php if (!empty($pendingApps)): ?>
        <div class="alert alert-success">
            <strong>&#128203; <?php echo count($pendingApps); ?> pending quest application(s) awaiting your review!</strong>
            <a href="guilds.php" class="action-link" style="margin-left: 10px;">Review Applications &rarr;</a>
        </div>
    <?php endif; ?>
    
    <?php if ($myPendingApps > 0): ?>
        <div class="alert" style="background-color: rgba(240, 192, 64, 0.15); border: 1px solid var(--color-gold); color: var(--color-gold);">
            <strong>&#128203; You have <?php echo $myPendingApps; ?> pending quest application(s).</strong>
            <a href="profile.php" class="action-link" style="margin-left: 10px;">Check Status &rarr;</a>
        </div>
    <?php endif; ?>
    
    <!-- Quick Actions -->
    <div class="grid-2" style="margin-bottom: 30px;">
        <div class="card">
            <h2 class="card-title">Quick Actions</h2>
            <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 15px;">
                <a href="quests.php" class="btn btn-primary" style="text-align: center;">Browse Quest Board</a>
                <a href="adventurers.php" class="btn btn-secondary" style="text-align: center;">View Adventurer Directory</a>
                <a href="guilds.php" class="btn btn-secondary" style="text-align: center;">View Guilds</a>
                <a href="bank.php" class="btn btn-secondary" style="text-align: center;">&#128176; Gold Bank</a>
                <a href="reports.php" class="btn btn-secondary" style="text-align: center;">View Guild Reports</a>
                <?php if ($_SESSION['role'] === 'guild_master'): ?>
                    <a href="quest_add.php" class="btn btn-success" style="text-align: center;">Post New Quest</a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <h2 class="card-title">Your Profile</h2>
            <div style="margin-top: 15px;">
                <p><strong>Username:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['email']); ?></p>
                <p><strong>Full Name:</strong> <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                <p><strong>Role:</strong> <?php echo ($_SESSION['role'] === 'guild_master') ? 'Guild Master' : 'Adventurer'; ?></p>
                <p><strong>Class:</strong> <?php echo htmlspecialchars($_SESSION['class'] ?? 'Not Set'); ?></p>
                <p><strong>Level:</strong> <?php echo intval($_SESSION['level']); ?></p>
                <p><strong>Guild:</strong> <?php echo $userGuild ? htmlspecialchars($userGuild['guild_name']) : 'None'; ?></p>
                <p><strong>Joined:</strong> <?php echo formatDate($currentUser['created_at']); ?></p>
            </div>
            <a href="profile.php" class="btn btn-primary btn-sm" style="margin-top: 15px;">Edit Profile</a>
        </div>
    </div>
    
    <!-- Recent Quests -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
            <h2 class="card-title" style="margin-bottom: 0;">Recent Quests</h2>
            <a href="quests.php" class="action-link">View All &rarr;</a>
        </div>
        
        <?php if (!empty($recentQuests)): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Quest Title</th>
                            <th>Difficulty</th>
                            <th>Status</th>
                            <th>Reward</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentQuests as $quest): ?>
                            <tr>
                                <td>
                                    <a href="quests.php" class="action-link"><?php echo htmlspecialchars($quest['title']); ?></a>
                                </td>
                                <td><?php echo getDifficultyBadge($quest['difficulty']); ?></td>
                                <td><?php echo getStatusBadge($quest['status']); ?></td>
                                <td><?php echo htmlspecialchars($quest['reward']); ?></td>
                                <td><?php echo formatDate($quest['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">&#128220;</div>
                <p>No quests have been posted yet.</p>
                <?php if ($_SESSION['role'] === 'guild_master'): ?>
                    <a href="quest_add.php" class="btn btn-primary btn-sm" style="margin-top: 15px;">Post Your First Quest</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Pending Applications (Guild Master only) -->
    <?php if (!empty($pendingApps)): ?>
        <div class="card" style="margin-top: 20px;">
            <h2 class="card-title">Pending Quest Applications</h2>
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
                        <?php foreach ($pendingApps as $app): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($app['applicant_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($app['class'] ?? 'N/A'); ?></td>
                                <td><?php echo $app['level']; ?></td>
                                <td><?php echo htmlspecialchars($app['quest_title']); ?></td>
                                <td><?php echo getDifficultyBadge($app['difficulty']); ?></td>
                                <td><?php echo formatDate($app['applied_at']); ?></td>
                                <td>
                                    <a href="application_review.php?id=<?php echo $app['app_id']; ?>&action=approve" class="btn btn-success btn-sm" onclick="return confirm('Approve this application?');">Approve</a>
                                    <a href="application_review.php?id=<?php echo $app['app_id']; ?>&action=reject" class="btn btn-danger btn-sm" onclick="return confirm('Reject this application?');">Reject</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
