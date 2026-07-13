<?php
/**
 * Reusable Functions
 * Adventurer Guild Registration & Quest Board
 */

// Include database connection
require_once __DIR__ . '/../config/database.php';

/**
 * Sanitize user input to prevent XSS attacks.
 * @param string $data
 * @return string
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate an email address format.
 * @param string $email
 * @return bool
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Display a flash message (success or error).
 * @param string $message
 * @param string $type 'success' or 'error'
 * @return string HTML
 */
function displayFlashMessage($message = '', $type = 'success') {
    if (!empty($message)) {
        $alertClass = ($type === 'error') ? 'alert-error' : 'alert-success';
        echo "<div class='alert {$alertClass}'>{$message}</div>";
    }
}

/**
 * Get a user by their ID from the database.
 * @param mysqli $conn
 * @param int $userId
 * @return array|false
 */
function getUserById($conn, $userId) {
    $stmt = $conn->prepare("SELECT user_id, username, email, full_name, role, class, level, bio, guild_id, avatar, created_at FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user;
}

/**
 * Get a quest by its ID from the database.
 * @param mysqli $conn
 * @param int $questId
 * @return array|false
 */
function getQuestById($conn, $questId) {
    $stmt = $conn->prepare("SELECT q.*, u1.full_name AS assigned_to_name, u2.full_name AS created_by_name FROM quests q LEFT JOIN users u1 ON q.assigned_to = u1.user_id LEFT JOIN users u2 ON q.created_by = u2.user_id WHERE q.quest_id = ?");
    $stmt->bind_param("i", $questId);
    $stmt->execute();
    $result = $stmt->get_result();
    $quest = $result->fetch_assoc();
    $stmt->close();
    return $quest;
}

/**
 * Get all quests with optional filtering.
 * @param mysqli $conn
 * @param string $status Optional status filter
 * @return array
 */
function getAllQuests($conn, $status = '') {
    $sql = "SELECT q.*, u.full_name AS assigned_to_name FROM quests q LEFT JOIN users u ON q.assigned_to = u.user_id ORDER BY q.created_at DESC";
    if (!empty($status)) {
        $sql = "SELECT q.*, u.full_name AS assigned_to_name FROM quests q LEFT JOIN users u ON q.assigned_to = u.user_id WHERE q.status = ? ORDER BY q.created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $status);
        $stmt->execute();
        $result = $stmt->get_result();
        $quests = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        $result = $conn->query($sql);
        $quests = $result->fetch_all(MYSQLI_ASSOC);
    }
    return $quests;
}

/**
 * Get all adventurers (users).
 * @param mysqli $conn
 * @return array
 */
function getAllAdventurers($conn) {
    $result = $conn->query("SELECT user_id, username, full_name, role, class, level, bio, guild_id, avatar, created_at FROM users ORDER BY level DESC");
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get quest statistics for reports page.
 * @param mysqli $conn
 * @return array
 */
function getQuestStatistics($conn) {
    $stats = array();
    
    // Total quests
    $result = $conn->query("SELECT COUNT(*) AS total FROM quests");
    $stats['total_quests'] = $result->fetch_assoc()['total'];
    
    // Quests by status
    $result = $conn->query("SELECT status, COUNT(*) AS count FROM quests GROUP BY status");
    $stats['by_status'] = $result->fetch_all(MYSQLI_ASSOC);
    
    // Quests by difficulty
    $result = $conn->query("SELECT difficulty, COUNT(*) AS count FROM quests GROUP BY difficulty ORDER BY FIELD(difficulty, 'EASY', 'MEDIUM', 'HARD', 'LEGENDARY')");
    $stats['by_difficulty'] = $result->fetch_all(MYSQLI_ASSOC);
    
    // Total adventurers
    $result = $conn->query("SELECT COUNT(*) AS total FROM users");
    $stats['total_adventurers'] = $result->fetch_assoc()['total'];
    
    // Adventurers by class
    $result = $conn->query("SELECT class, COUNT(*) AS count FROM users WHERE class IS NOT NULL AND class != '' GROUP BY class ORDER BY count DESC");
    $stats['by_class'] = $result->fetch_all(MYSQLI_ASSOC);
    
    // Adventurers by guild
    $result = $conn->query("SELECT g.guild_name, COUNT(u.user_id) AS count FROM guilds g LEFT JOIN users u ON g.guild_id = u.guild_id GROUP BY g.guild_id ORDER BY count DESC");
    $stats['by_guild'] = $result->fetch_all(MYSQLI_ASSOC);
    
    return $stats;
}

/**
 * Get difficulty badge HTML.
 * @param string $difficulty
 * @return string HTML
 */
function getDifficultyBadge($difficulty) {
    switch (strtoupper($difficulty)) {
        case 'EASY':
            return "<span class='badge badge-easy'>Easy</span>";
        case 'MEDIUM':
            return "<span class='badge badge-medium'>Medium</span>";
        case 'HARD':
            return "<span class='badge badge-hard'>Hard</span>";
        case 'LEGENDARY':
            return "<span class='badge badge-legendary'>Legendary</span>";
        default:
            return "<span class='badge'>$difficulty</span>";
    }
}

/**
 * Get status badge HTML.
 * @param string $status
 * @return string HTML
 */
function getStatusBadge($status) {
    switch (strtoupper($status)) {
        case 'OPEN':
            return "<span class='status-badge status-open'>Open</span>";
        case 'IN_PROGRESS':
            return "<span class='status-badge status-progress'>In Progress</span>";
        case 'COMPLETED':
            return "<span class='status-badge status-completed'>Completed</span>";
        default:
            return "<span class='status-badge'>$status</span>";
    }
}

/**
 * Format a date string.
 * @param string $dateStr
 * @return string
 */
function formatDate($dateStr) {
    return date("M d, Y", strtotime($dateStr));
}

/**
 * Handle profile picture upload.
 * @param array $file The $_FILES['avatar'] data
 * @param int $userId The user's ID
 * @return string|false Filename on success, false on failure
 */
function uploadProfilePicture($file, $userId) {
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Check file size (2MB max)
    if ($file['size'] > 2097152) {
        return false;
    }
    
    // Check file type
    $allowedTypes = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
    if (!in_array($file['type'], $allowedTypes)) {
        return false;
    }
    
    // Get file extension
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $allowedExts = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    if (!in_array(strtolower($ext), $allowedExts)) {
        return false;
    }
    
    // Generate unique filename
    $filename = 'user_' . $userId . '_' . time() . '.' . $ext;
    $uploadPath = UPLOAD_DIR . 'avatars/' . $filename;
    
    // Delete old avatar if exists
    $oldFiles = glob(UPLOAD_DIR . 'avatars/user_' . $userId . '_*');
    foreach ($oldFiles as $oldFile) {
        if (is_file($oldFile)) {
            unlink($oldFile);
        }
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return $filename;
    }
    
    return false;
}

/**
 * Get a list of all guilds.
 * @param mysqli $conn
 * @return array
 */
function getAllGuilds($conn) {
    $result = $conn->query("SELECT * FROM guilds ORDER BY guild_name ASC");
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get a guild by its ID.
 * @param mysqli $conn
 * @param int $guildId
 * @return array|false
 */
function getGuildById($conn, $guildId) {
    $stmt = $conn->prepare("SELECT * FROM guilds WHERE guild_id = ?");
    $stmt->bind_param("i", $guildId);
    $stmt->execute();
    $result = $stmt->get_result();
    $guild = $result->fetch_assoc();
    $stmt->close();
    return $guild;
}

/**
 * Get members of a guild.
 * @param mysqli $conn
 * @param int $guildId
 * @return array
 */
function getGuildMembers($conn, $guildId) {
    $stmt = $conn->prepare("SELECT u.user_id, u.username, u.full_name, u.class, u.level, u.avatar, u.role FROM users u WHERE u.guild_id = ? ORDER BY u.level DESC");
    $stmt->bind_param("i", $guildId);
    $stmt->execute();
    $result = $stmt->get_result();
    $members = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $members;
}

/**
 * Create a new quest application.
 * @param mysqli $conn
 * @param int $questId
 * @param int $userId
 * @return bool
 */
function createQuestApplication($conn, $questId, $userId) {
    // Check if user already applied
    $stmt = $conn->prepare("SELECT app_id FROM quest_applications WHERE quest_id = ? AND applicant_id = ? AND status IN ('PENDING', 'APPROVED')");
    $stmt->bind_param("ii", $questId, $userId);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        return 'duplicate';
    }
    $stmt->close();
    
    $stmt = $conn->prepare("INSERT INTO quest_applications (quest_id, applicant_id, status, applied_at) VALUES (?, ?, 'PENDING', NOW())");
    $stmt->bind_param("ii", $questId, $userId);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

/**
 * Get applications for a quest.
 * @param mysqli $conn
 * @param int $questId
 * @return array
 */
function getQuestApplications($conn, $questId) {
    $stmt = $conn->prepare("SELECT a.*, u.username, u.full_name, u.class, u.level FROM quest_applications a JOIN users u ON a.applicant_id = u.user_id WHERE a.quest_id = ? ORDER BY a.applied_at DESC");
    $stmt->bind_param("i", $questId);
    $stmt->execute();
    $result = $stmt->get_result();
    $apps = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $apps;
}

/**
 * Update application status (approve/reject).
 * @param mysqli $conn
 * @param int $appId
 * @param string $status
 * @return bool
 */
function updateApplicationStatus($conn, $appId, $status) {
    $stmt = $conn->prepare("UPDATE quest_applications SET status = ?, reviewed_at = NOW() WHERE app_id = ? AND status = 'PENDING'");
    $stmt->bind_param("si", $status, $appId);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

/**
 * Get user's wallet balance.
 * @param mysqli $conn
 * @param int $userId
 * @return array
 */
function getWalletBalance($conn, $userId) {
    $stmt = $conn->prepare("SELECT balance FROM wallets WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $wallet = $result->fetch_assoc();
    $stmt->close();
    return $wallet;
}

/**
 * Get transaction history for a user.
 * @param mysqli $conn
 * @param int $userId
 * @param int $limit
 * @return array
 */
function getTransactionHistory($conn, $userId, $limit = 20) {
    $stmt = $conn->prepare("SELECT t.*, q.title AS quest_title FROM transactions t LEFT JOIN quests q ON t.quest_id = q.quest_id WHERE t.user_id = ? ORDER BY t.created_at DESC LIMIT ?");
    $stmt->bind_param("ii", $userId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $transactions = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $transactions;
}

/**
 * Add gold to user wallet and record transaction.
 * @param mysqli $conn
 * @param int $userId
 * @param float $amount
 * @param string $type
 * @param string $description
 * @param int|null $questId
 * @return bool
 */
function addGoldToWallet($conn, $userId, $amount, $type, $description, $questId = null) {
    // Update wallet balance
    $stmt = $conn->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ?");
    $stmt->bind_param("di", $amount, $userId);
    $stmt->execute();
    $stmt->close();
    
    // Record transaction
    $stmt = $conn->prepare("INSERT INTO transactions (user_id, amount, type, description, quest_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("idssi", $userId, $amount, $type, $description, $questId);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

/**
 * Spend gold from user wallet.
 * @param mysqli $conn
 * @param int $userId
 * @param float $amount
 * @param string $description
 * @return bool|false (false if insufficient funds)
 */
function spendGold($conn, $userId, $amount, $description) {
    // Check balance
    $wallet = getWalletBalance($conn, $userId);
    if (!$wallet || $wallet['balance'] < $amount) {
        return false;
    }
    
    // Deduct from wallet
    $stmt = $conn->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = ?");
    $stmt->bind_param("di", $amount, $userId);
    $stmt->execute();
    $stmt->close();
    
    // Record transaction
    $stmt = $conn->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'SPEND', ?)");
    $stmt->bind_param("ids", $userId, $amount, $description);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

/**
 * Get application status badge HTML.
 * @param string $status
 * @return string HTML
 */
function getApplicationStatusBadge($status) {
    switch (strtoupper($status)) {
        case 'PENDING':
            return "<span class='status-badge status-open'>Pending</span>";
        case 'APPROVED':
            return "<span class='status-badge status-completed'>Approved</span>";
        case 'REJECTED':
            return "<span class='status-badge status-progress'>Rejected</span>";
        default:
            return "<span class='status-badge'>$status</span>";
    }
}

/**
 * Format a gold amount for display.
 * @param float $amount
 * @return string
 */
function formatGold($amount) {
    return number_format($amount, 0) . " Gold";
}

/**
 * Get the avatar filename for a user.
 * @param int $userId
 * @return string
 */
function getUserAvatar($userId) {
    $avatarDir = UPLOAD_DIR . 'avatars/';
    $files = glob($avatarDir . 'user_' . $userId . '_*');
    if (!empty($files)) {
        return basename($files[0]);
    }
    return null;
}

/**
 * Generate a random adventure-themed welcome message.
 * @param string $name
 * @return string
 */
function getRandomWelcome($name) {
    $messages = array(
        "Welcome back, brave adventurer $name!",
        "Greetings, $name! The guild awaits your next quest.",
        "Hail, $name! The quest board has new opportunities.",
        "Well met, $name! Fortunes favor the bold.",
        "Salutations, $name! Adventure calls!"
    );
    return $messages[array_rand($messages)];
}
?>
