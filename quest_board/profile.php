<?php
/**
 * Profile Page
 * View and edit own profile, or view other adventurers' profiles.
 * Includes avatar, guild, and wallet information.
 */
require_once __DIR__ . '/includes/auth_check.php';
$pageTitle = "Profile";
require_once __DIR__ . '/includes/functions.php';

// Check if viewing own profile or another adventurer's profile
$profileUserId = isset($_GET['id']) ? intval($_GET['id']) : $_SESSION['user_id'];
$isOwnProfile = ($profileUserId == $_SESSION['user_id']);

$conn = getConnection();
$user = getUserById($conn, $profileUserId);
$userGuild = null;
if ($user && $user['guild_id']) {
    $userGuild = getGuildById($conn, $user['guild_id']);
}
$userAvatar = getUserAvatar($profileUserId);
closeConnection($conn);

if (!$user) {
    header("Location: adventurers.php");
    exit();
}

$errors = array();
$successMessage = "";

// Process profile update (only for own profile)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isOwnProfile) {
    $fullName = sanitizeInput($_POST['full_name'] ?? '');
    $playerClass = sanitizeInput($_POST['class'] ?? '');
    $level = intval($_POST['level'] ?? 1);
    $bio = sanitizeInput($_POST['bio'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $guildId = !empty($_POST['guild_id']) ? intval($_POST['guild_id']) : NULL;
    
    // Validation
    if (empty($fullName)) {
        $errors[] = "Full name is required.";
    }
    
    if (!isValidEmail($email)) {
        $errors[] = "Please enter a valid email address.";
    }
    
    if ($level < 1 || $level > 100) {
        $errors[] = "Level must be between 1 and 100.";
    }
    
    // Validate guild
    if ($guildId !== NULL) {
        $conn = getConnection();
        $guild = getGuildById($conn, $guildId);
        if (!$guild) {
            $errors[] = "Invalid guild selection.";
        }
        closeConnection($conn);
    }
    
    if (empty($errors)) {
        $conn = getConnection();
        
        // Check if email is taken by another user
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $stmt->bind_param("si", $email, $profileUserId);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Email is already taken by another adventurer.";
        }
        $stmt->close();
        
        if (empty($errors)) {
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, class = ?, level = ?, bio = ?, email = ?, guild_id = ? WHERE user_id = ?");
            $stmt->bind_param("ssissii", $fullName, $playerClass, $level, $bio, $email, $guildId, $profileUserId);
            
            if ($stmt->execute()) {
                $successMessage = "Profile updated successfully!";
                
                // Update session data
                $_SESSION['full_name'] = $fullName;
                $_SESSION['class'] = $playerClass;
                $_SESSION['level'] = $level;
                $_SESSION['email'] = $email;
                
                // Handle avatar upload if present
                if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                    $avatarFile = uploadProfilePicture($_FILES['avatar'], $profileUserId);
                    if ($avatarFile) {
                        $stmt2 = $conn->prepare("UPDATE users SET avatar = ? WHERE user_id = ?");
                        $stmt2->bind_param("si", $avatarFile, $profileUserId);
                        $stmt2->execute();
                        $stmt2->close();
                        $userAvatar = $avatarFile;
                    }
                }
                
                // Refresh user data
                $user = getUserById($conn, $profileUserId);
                if ($user && $user['guild_id']) {
                    $userGuild = getGuildById($conn, $user['guild_id']);
                }
            } else {
                $errors[] = "Failed to update profile.";
            }
            
            $stmt->close();
        }
        
        closeConnection($conn);
    }
}

// Get user's quests if viewing own profile
$userQuests = array();
$wallet = null;
if ($isOwnProfile) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT q.* FROM quests q WHERE q.created_by = ? OR q.assigned_to = ? ORDER BY q.created_at DESC");
    $stmt->bind_param("ii", $profileUserId, $profileUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    $userQuests = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Get wallet
    $wallet = getWalletBalance($conn, $profileUserId);
    
    // Get transaction history (last 10)
    $transactions = getTransactionHistory($conn, $profileUserId, 10);
    
    // Get my quest applications
    $stmt = $conn->prepare("SELECT a.*, q.title AS quest_title, q.difficulty FROM quest_applications a JOIN quests q ON a.quest_id = q.quest_id WHERE a.applicant_id = ? ORDER BY a.applied_at DESC");
    $stmt->bind_param("i", $profileUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    $myApplications = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    closeConnection($conn);
}
?>

<?php include_once __DIR__ . '/includes/header.php'; ?>

<div class="container">
    <a href="<?php echo $isOwnProfile ? 'dashboard.php' : 'adventurers.php'; ?>" class="back-link">
        &larr; <?php echo $isOwnProfile ? 'Back to Dashboard' : 'Back to Adventurers'; ?>
    </a>
    
    <div class="profile-header">
        <div class="profile-avatar" style="background-image: <?php echo $userAvatar ? 'url(uploads/avatars/' . htmlspecialchars($userAvatar) . '); background-size: cover; background-position: center;' : ''; ?>">
            <?php if (!$userAvatar): ?>
                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
            <?php endif; ?>
        </div>
        <div class="profile-info">
            <h2><?php echo htmlspecialchars($user['full_name']); ?></h2>
            <p>@<?php echo htmlspecialchars($user['username']); ?> &bull; 
               <?php echo htmlspecialchars($user['class'] ?? 'Unknown Class'); ?> &bull; 
               Level <?php echo intval($user['level']); ?></p>
            <?php if ($userGuild): ?>
                <p style="color: var(--color-gold); margin-top: 5px;">
                    <strong>Guild:</strong> <?php echo htmlspecialchars($userGuild['guild_name']); ?>
                </p>
            <?php endif; ?>
            <?php 
            if ($user['role'] === 'guild_master') {
                echo '<span class="badge badge-legendary" style="margin-top: 5px;">Guild Master</span>';
            } else {
                echo '<span class="badge badge-easy" style="margin-top: 5px;">Adventurer</span>';
            }
            ?>
        </div>
    </div>
    
    <div class="grid-2">
        <!-- Profile Details -->
        <div class="card">
            <h2 class="card-title">Profile Details</h2>
            <div style="margin-top: 15px;">
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong>Class:</strong> <?php echo htmlspecialchars($user['class'] ?? 'Not Set'); ?></p>
                <p><strong>Level:</strong> <?php echo intval($user['level']); ?></p>
                <p><strong>Role:</strong> <?php echo ($user['role'] === 'guild_master') ? 'Guild Master' : 'Adventurer'; ?></p>
                <p><strong>Guild:</strong> <?php echo $userGuild ? htmlspecialchars($userGuild['guild_name']) : 'None'; ?></p>
                <p><strong>Joined:</strong> <?php echo formatDate($user['created_at']); ?></p>
                <hr style="border-color: var(--color-border); margin: 15px 0;">
                <p><strong>Bio:</strong></p>
                <p style="color: var(--color-text-muted);"><?php echo !empty($user['bio']) ? nl2br(htmlspecialchars($user['bio'])) : 'No bio provided.'; ?></p>
            </div>
        </div>
        
        <!-- Wallet & Banking -->
        <?php if ($isOwnProfile): ?>
            <div class="card">
                <h2 class="card-title">&#128176; Gold Wallet</h2>
                <div style="margin-top: 15px; text-align: center;">
                    <div style="font-size: 2.5rem; font-weight: bold; color: var(--color-gold);">
                        <?php echo formatGold($wallet['balance'] ?? 0); ?>
                    </div>
                    <p style="color: var(--color-text-muted);">Current Balance</p>
                </div>
                
                <hr style="border-color: var(--color-border); margin: 15px 0;">
                
                <h3 style="color: var(--color-gold); margin-bottom: 10px;">Recent Transactions</h3>
                <?php if (!empty($transactions)): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Description</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $txn): ?>
                                    <tr>
                                        <td>
                                            <?php 
                                            if ($txn['type'] === 'REWARD') {
                                                echo '<span style="color: var(--color-success);">Reward</span>';
                                            } elseif ($txn['type'] === 'SPEND') {
                                                echo '<span style="color: var(--color-error);">Spent</span>';
                                            } else {
                                                echo htmlspecialchars($txn['type']);
                                            }
                                            ?>
                                        </td>
                                        <td style="color: <?php echo $txn['amount'] > 0 ? 'var(--color-success)' : 'var(--color-error)'; ?>">
                                            <?php echo ($txn['amount'] > 0 ? '+' : '-') . formatGold(abs($txn['amount'])); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($txn['description']); ?></td>
                                        <td><?php echo formatDate($txn['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="bank.php" class="btn btn-primary btn-sm" style="margin-top: 10px;">Full Bank Statement</a>
                <?php else: ?>
                    <p style="color: var(--color-text-muted);">No transactions yet. Complete quests to earn gold!</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Edit Profile Form (only for own profile) -->
    <?php if ($isOwnProfile): ?>
        <div class="card" style="margin-top: 20px;">
            <h2 class="card-title">Edit Profile</h2>
            
            <?php if (!empty($successMessage)): ?>
                <?php displayFlashMessage($successMessage, 'success'); ?>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <strong>Errors:</strong><br>
                    <?php foreach ($errors as $error): ?>
                        &bull; <?php echo htmlspecialchars($error); ?><br>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="profile.php" enctype="multipart/form-data">
                <div class="grid-2">
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" required maxlength="100"
                               value="<?php echo htmlspecialchars($user['full_name']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required maxlength="100"
                               value="<?php echo htmlspecialchars($user['email']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="class">Class</label>
                        <input type="text" id="class" name="class" maxlength="50"
                               value="<?php echo htmlspecialchars($user['class'] ?? ''); ?>"
                               placeholder="e.g., Wizard, Warrior, Rogue">
                    </div>
                    
                    <div class="form-group">
                        <label for="level">Level</label>
                        <input type="number" id="level" name="level" min="1" max="100"
                               value="<?php echo intval($user['level']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="guild_id">Guild Affiliation</label>
                        <select id="guild_id" name="guild_id">
                            <option value="">-- No Guild --</option>
                            <?php 
                            $conn = getConnection();
                            $allGuilds = getAllGuilds($conn);
                            closeConnection($conn);
                            foreach ($allGuilds as $guild): 
                            ?>
                                <option value="<?php echo $guild['guild_id']; ?>" 
                                        <?php echo $user['guild_id'] == $guild['guild_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($guild['guild_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="avatar">Update Profile Picture</label>
                        <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp">
                        <small style="color: var(--color-text-muted);">JPG, PNG, GIF, or WebP. Max 2MB.</small>
                        <?php if ($userAvatar): ?>
                            <br><img src="uploads/avatars/<?php echo htmlspecialchars($userAvatar); ?>" alt="Current avatar" style="width: 60px; height: 60px; border-radius: 50%; margin-top: 5px; object-fit: cover;">
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="bio">Bio / Background</label>
                    <textarea id="bio" name="bio" placeholder="Tell us about your adventurer's background..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
        
        <!-- My Quest Applications -->
        <div class="card" style="margin-top: 20px;">
            <h2 class="card-title">My Quest Applications</h2>
            
            <?php if (!empty($myApplications)): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Quest</th>
                                <th>Difficulty</th>
                                <th>Status</th>
                                <th>Applied</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($myApplications as $app): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($app['quest_title']); ?></strong></td>
                                    <td><?php echo getDifficultyBadge($app['difficulty']); ?></td>
                                    <td><?php echo getApplicationStatusBadge($app['status']); ?></td>
                                    <td><?php echo formatDate($app['applied_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <a href="quests.php" class="btn btn-primary btn-sm" style="margin-top: 10px;">Browse More Quests</a>
            <?php else: ?>
                <p style="color: var(--color-text-muted);">You haven't applied for any quests yet.</p>
            <?php endif; ?>
        </div>
        
        <!-- Your Quests -->
        <div class="card" style="margin-top: 20px;">
            <h2 class="card-title">Your Quests</h2>
            
            <?php if (!empty($userQuests)): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Difficulty</th>
                                <th>Status</th>
                                <th>Reward</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($userQuests as $quest): ?>
                                <tr>
                                    <td>
                                        <?php 
                                        if ($quest['created_by'] == $profileUserId) {
                                            echo '<strong>Created:</strong> ';
                                        } else {
                                            echo '<strong>Assigned:</strong> ';
                                        }
                                        echo htmlspecialchars($quest['title']);
                                        ?>
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
                    <p>You haven't created or been assigned any quests yet.</p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
