<?php
/**
 * Adventurers Directory Page
 * Display all registered adventurers with their details.
 */
require_once __DIR__ . '/includes/auth_check.php';
$pageTitle = "Adventurers";
require_once __DIR__ . '/includes/functions.php';

$conn = getConnection();

// Get adventurers with guild info
$result = $conn->query("
    SELECT u.*, g.guild_name, g.guild_color 
    FROM users u 
    LEFT JOIN guilds g ON u.guild_id = g.guild_id 
    ORDER BY u.level DESC
");
$adventurers = $result->fetch_all(MYSQLI_ASSOC);
closeConnection($conn);
?>

<?php include_once __DIR__ . '/includes/header.php'; ?>

<div class="container">
    <h1 class="page-title">Adventurer Directory</h1>
    <p class="page-subtitle">View all registered adventurers in the Guild</p>
    
    <?php if (!empty($adventurers)): ?>
        <div class="table-container">
            <table>
                <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Username</th>
                            <th>Class</th>
                            <th>Guild</th>
                            <th>Level</th>
                        <th>Role</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($adventurers as $adv): ?>
                        <tr>
                            <td>
                                <?php
                                $advAvatar = getUserAvatar($adv['user_id']);
                                if ($advAvatar):
                                ?>
                                    <img src="uploads/avatars/<?php echo htmlspecialchars($advAvatar); ?>" alt="Avatar" style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover; margin-right: 8px; vertical-align: middle;">
                                <?php endif; ?>
                                <strong><?php echo htmlspecialchars($adv['full_name']); ?></strong>
                            </td>
                            <td>@<?php echo htmlspecialchars($adv['username']); ?></td>
                            <td><?php echo htmlspecialchars($adv['class'] ?? 'Not Set'); ?></td>
                            <td><?php echo $adv['guild_name'] ? htmlspecialchars($adv['guild_name']) : '<em style="color: var(--color-text-muted);">No Guild</em>'; ?></td>
                            <td>
                                <?php 
                                $level = intval($adv['level']);
                                echo $level;
                                // Simple visual indicator for high levels
                                if ($level >= 50) {
                                    echo ' <span style="color: var(--color-gold);">&#9733;</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                if ($adv['role'] === 'guild_master') {
                                    echo '<span class="badge badge-legendary">Guild Master</span>';
                                } else {
                                    echo '<span class="badge badge-easy">Adventurer</span>';
                                }
                                ?>
                            </td>
                            <td><?php echo formatDate($adv['created_at']); ?></td>
                            <td>
                                <a href="profile.php?id=<?php echo $adv['user_id']; ?>" class="action-link">View Profile</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <p style="text-align: center; color: var(--color-text-muted); margin-top: 15px;">
            Total: <?php echo count($adventurers); ?> adventurer(s) registered in the Guild
        </p>
    <?php else: ?>
        <div class="card empty-state">
            <div class="empty-icon">&#128101;</div>
            <p>No adventurers have registered yet.</p>
            <p style="margin-top: 10px;">Be the first to join the Guild!</p>
            <a href="register.php" class="btn btn-primary" style="margin-top: 15px;">Register Now</a>
        </div>
    <?php endif; ?>
    
    <!-- Adventurer Cards Grid -->
    <?php if (!empty($adventurers)): ?>
        <h2 style="color: var(--color-gold); margin: 30px 0 20px;">Adventurer Cards</h2>
        <div class="grid-3">
                    <?php foreach ($adventurers as $adv): ?>
                <div class="card" style="text-align: center;">
                    <?php
                    $advAvatar = getUserAvatar($adv['user_id']);
                    ?>
                    <div class="profile-avatar" style="margin: 0 auto 15px; <?php echo $advAvatar ? 'background-image: url(uploads/avatars/' . htmlspecialchars($advAvatar) . '); background-size: cover; background-position: center;' : ''; ?>">
                        <?php if (!$advAvatar): ?>
                            <?php echo strtoupper(substr($adv['full_name'], 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <h3 style="color: var(--color-gold); margin-bottom: 5px;"><?php echo htmlspecialchars($adv['full_name']); ?></h3>
                    <p style="color: var(--color-text-muted); margin-bottom: 5px;">@<?php echo htmlspecialchars($adv['username']); ?></p>
                    <p style="margin-bottom: 5px;">
                        <strong>Class:</strong> <?php echo htmlspecialchars($adv['class'] ?? 'Not Set'); ?>
                    </p>
                    <?php if ($adv['guild_name']): ?>
                    <p style="margin-bottom: 5px;">
                        <strong>Guild:</strong> <?php echo htmlspecialchars($adv['guild_name']); ?>
                    </p>
                    <?php endif; ?>
                    <p style="margin-bottom: 5px;">
                        <strong>Level:</strong> <?php echo intval($adv['level']); ?>
                    </p>
                    <p style="margin-bottom: 15px;">
                        <?php 
                        if ($adv['role'] === 'guild_master') {
                            echo '<span class="badge badge-legendary">Guild Master</span>';
                        } else {
                            echo '<span class="badge badge-easy">Adventurer</span>';
                        }
                        ?>
                    </p>
                    <a href="profile.php?id=<?php echo $adv['user_id']; ?>" class="btn btn-primary btn-sm">View Profile</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
