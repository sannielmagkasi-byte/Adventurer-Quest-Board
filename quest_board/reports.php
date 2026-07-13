<?php
/**
 * Reports / Summary Page
 * Display guild statistics and summaries.
 */
require_once __DIR__ . '/includes/auth_check.php';
$pageTitle = "Reports";
require_once __DIR__ . '/includes/functions.php';

$conn = getConnection();

// Get all statistics
$stats = getQuestStatistics($conn);

// Get quests by status for detail view
$questsByStatus = array();
$result = $conn->query("SELECT q.*, u.full_name AS assigned_to_name FROM quests q LEFT JOIN users u ON q.assigned_to = u.user_id");
$allQuests = $result->fetch_all(MYSQLI_ASSOC);

// Count quests by status
$statusCounts = array('OPEN' => 0, 'IN_PROGRESS' => 0, 'COMPLETED' => 0);
$difficultyCounts = array('EASY' => 0, 'MEDIUM' => 0, 'HARD' => 0, 'LEGENDARY' => 0);
foreach ($allQuests as $quest) {
    if (isset($statusCounts[$quest['status']])) {
        $statusCounts[$quest['status']]++;
    }
    if (isset($difficultyCounts[$quest['difficulty']])) {
        $difficultyCounts[$quest['difficulty']]++;
    }
}

// Get top adventurers by level
$result = $conn->query("SELECT user_id, username, full_name, class, level, role FROM users ORDER BY level DESC LIMIT 10");
$topAdventurers = $result->fetch_all(MYSQLI_ASSOC);

closeConnection($conn);
?>

<?php include_once __DIR__ . '/includes/header.php'; ?>

<div class="container">
    <h1 class="page-title">Guild Reports</h1>
    <p class="page-subtitle">Summary statistics and guild overview</p>
    
    <!-- Overview Stats -->
    <div class="grid-3">
        <div class="stat-card">
            <div class="stat-number"><?php echo $stats['total_quests']; ?></div>
            <div class="stat-label">Total Quests Posted</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $stats['total_adventurers']; ?></div>
            <div class="stat-label">Registered Adventurers</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo isset($statusCounts['COMPLETED']) ? $statusCounts['COMPLETED'] : 0; ?></div>
            <div class="stat-label">Completed Quests</div>
        </div>
    </div>
    
    <!-- Quests by Status -->
    <div class="card" style="margin-top: 20px;">
        <h2 class="card-title">Quests by Status</h2>
        <div style="margin-top: 15px;">
            <?php foreach ($statusCounts as $status => $count): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--color-border);">
                    <span>
                        <?php echo getStatusBadge($status); ?>
                    </span>
                    <strong style="font-size: 1.3rem; color: var(--color-gold);"><?php echo $count; ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Quests by Difficulty -->
    <div class="card">
        <h2 class="card-title">Quests by Difficulty</h2>
        <div style="margin-top: 15px;">
            <?php foreach ($difficultyCounts as $difficulty => $count): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--color-border);">
                    <span><?php echo getDifficultyBadge($difficulty); ?></span>
                    <strong style="font-size: 1.3rem; color: var(--color-gold);"><?php echo $count; ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Top Adventurers -->
    <div class="card">
        <h2 class="card-title">Top Adventurers (by Level)</h2>
        
        <?php if (!empty($topAdventurers)): ?>
            <div class="table-container" style="margin-top: 15px;">
                <table>
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Name</th>
                            <th>Class</th>
                            <th>Level</th>
                            <th>Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rank = 1;
                        foreach ($topAdventurers as $adv): 
                        ?>
                            <tr>
                                <td>
                                    <?php 
                                    if ($rank === 1) {
                                        echo '<strong style="color: var(--color-gold);">&#127942; 1st</strong>';
                                    } elseif ($rank === 2) {
                                        echo '<strong style="color: #c0c0c0;">&#129352; 2nd</strong>';
                                    } elseif ($rank === 3) {
                                        echo '<strong style="color: #cd7f32;">&#129353; 3rd</strong>';
                                    } else {
                                        echo $rank;
                                    }
                                    ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($adv['full_name']); ?></strong> (@<?php echo htmlspecialchars($adv['username']); ?>)</td>
                                <td><?php echo htmlspecialchars($adv['class'] ?? 'Not Set'); ?></td>
                                <td><?php echo intval($adv['level']); ?></td>
                                <td>
                                    <?php 
                                    if ($adv['role'] === 'guild_master') {
                                        echo '<span class="badge badge-legendary">Guild Master</span>';
                                    } else {
                                        echo '<span class="badge badge-easy">Adventurer</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php 
                            $rank++;
                        endforeach; 
                        ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>No adventurers registered yet.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Adventurers by Guild -->
    <div class="card">
        <h2 class="card-title">Adventurers by Guild</h2>
        
        <?php if (!empty($stats['by_guild'])): ?>
            <div style="margin-top: 15px;">
                <?php foreach ($stats['by_guild'] as $guildData): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--color-border);">
                        <span style="color: var(--color-gold);"><strong><?php echo htmlspecialchars($guildData['guild_name']); ?></strong></span>
                        <strong style="font-size: 1.3rem;"><?php echo $guildData['count']; ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state" style="padding: 20px;">
                <p>No guild data available yet.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Adventurers by Class -->
    <div class="card">
        <h2 class="card-title">Adventurers by Class</h2>
        
        <?php if (!empty($stats['by_class'])): ?>
            <div style="margin-top: 15px;">
                <?php foreach ($stats['by_class'] as $classData): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--color-border);">
                        <span style="color: var(--color-gold);"><strong><?php echo htmlspecialchars($classData['class']); ?></strong></span>
                        <strong style="font-size: 1.3rem;"><?php echo $classData['count']; ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state" style="padding: 20px;">
                <p>No class data available yet.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Quest Applications Summary -->
    <?php
    $result = $conn = getConnection();
    $result = $conn->query("SELECT status, COUNT(*) AS count FROM quest_applications GROUP BY status");
    $appStats = $result->fetch_all(MYSQLI_ASSOC);
    closeConnection($conn);
    ?>
    
    <div class="card">
        <h2 class="card-title">Quest Applications</h2>
        
        <?php if (!empty($appStats)): ?>
            <div style="margin-top: 15px;">
                <?php 
                $appLabels = array('PENDING' => 'Pending Review', 'APPROVED' => 'Approved', 'REJECTED' => 'Rejected');
                foreach ($appStats as $appStat): 
                ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--color-border);">
                        <span>
                            <?php echo getApplicationStatusBadge($appStat['status']); ?>
                        </span>
                        <strong style="font-size: 1.3rem; color: var(--color-gold);"><?php echo $appStat['count']; ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state" style="padding: 20px;">
                <p>No quest applications yet.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Print Button -->
    <div style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" class="btn btn-secondary">Print Report</button>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
