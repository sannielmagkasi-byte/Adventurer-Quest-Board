<?php
/**
 * Bank / Gold Wallet Page
 * Adventurers can view their gold balance, transaction history, and manage their gold.
 * Guild Masters can issue rewards.
 */
require_once __DIR__ . '/includes/auth_check.php';
$pageTitle = "Gold Bank";
require_once __DIR__ . '/includes/functions.php';

$conn = getConnection();

// Get user's wallet
$wallet = getWalletBalance($conn, $_SESSION['user_id']);
$balance = $wallet['balance'] ?? 0;

// Get full transaction history
$transactions = getTransactionHistory($conn, $_SESSION['user_id'], 100);

// Handle Guild Master reward issuance
$rewardMsg = "";
if ($_SESSION['role'] === 'guild_master' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['reward_adventurer_id']) && isset($_POST['reward_amount'])) {
        $targetId = intval($_POST['reward_adventurer_id']);
        $amount = floatval($_POST['reward_amount']);
        $description = sanitizeInput($_POST['reward_description'] ?? 'Quest Reward');
        $questId = !empty($_POST['reward_quest_id']) ? intval($_POST['reward_quest_id']) : null;
        
        if ($amount > 0) {
            $result = addGoldToWallet($conn, $targetId, $amount, 'REWARD', $description, $questId);
            if ($result) {
                // Mark quest as completed if quest_id provided
                if ($questId) {
                    $stmt = $conn->prepare("UPDATE quests SET status = 'COMPLETED' WHERE quest_id = ?");
                    $stmt->bind_param("i", $questId);
                    $stmt->execute();
                    $stmt->close();
                }
                $rewardMsg = "Successfully awarded " . formatGold($amount) . " to the adventurer!";
                
                // Refresh balance for the target user
                if ($targetId == $_SESSION['user_id']) {
                    $wallet = getWalletBalance($conn, $_SESSION['user_id']);
                    $balance = $wallet['balance'] ?? 0;
                }
            } else {
                $rewardMsg = "Failed to process reward.";
            }
        } else {
            $rewardMsg = "Invalid amount. Must be greater than 0.";
        }
    }
}

// Get adventurers for reward dropdown (GM only)
$adventurers = array();
if ($_SESSION['role'] === 'guild_master') {
    $result = $conn->query("SELECT user_id, full_name, username, class, level FROM users ORDER BY full_name ASC");
    $adventurers = $result->fetch_all(MYSQLI_ASSOC);
}

// Get quests for reward association (GM only)
$activeQuests = array();
if ($_SESSION['role'] === 'guild_master') {
    $stmt = $conn->prepare("SELECT quest_id, title FROM quests WHERE status != 'COMPLETED' ORDER BY title ASC");
    $stmt->execute();
    $activeQuests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

closeConnection($conn);
?>

<?php include_once __DIR__ . '/includes/header.php'; ?>

<div class="container">
    <h1 class="page-title">&#128176; Gold Bank</h1>
    <p class="page-subtitle">Manage your gold and view transaction history</p>
    
    <?php if (!empty($rewardMsg)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($rewardMsg); ?></div>
    <?php endif; ?>
    
    <!-- Wallet Overview -->
    <div class="grid-2" style="margin-bottom: 30px;">
        <div class="card" style="text-align: center;">
            <h2 class="card-title">Gold Balance</h2>
            <div style="font-size: 3rem; font-weight: bold; color: var(--color-gold); margin: 20px 0;">
                <?php echo formatGold($balance); ?>
            </div>
            <p style="color: var(--color-text-muted);">
                <?php 
                $earned = 0;
                $spent = 0;
                foreach ($transactions as $txn) {
                    if ($txn['amount'] > 0) $earned += $txn['amount'];
                    if ($txn['amount'] < 0) $spent += abs($txn['amount']);
                }
                echo "Total Earned: " . formatGold($earned) . " | Total Spent: " . formatGold($spent);
                ?>
            </p>
        </div>
        
        <!-- Guild Master: Issue Rewards -->
        <?php if ($_SESSION['role'] === 'guild_master'): ?>
            <div class="card">
                <h2 class="card-title">&#127942; Issue Reward</h2>
                <form method="POST" action="bank.php">
                    <div class="form-group">
                        <label for="reward_adventurer_id">Adventurer *</label>
                        <select id="reward_adventurer_id" name="reward_adventurer_id" required>
                            <option value="">-- Select Adventurer --</option>
                            <?php foreach ($adventurers as $adv): ?>
                                <option value="<?php echo $adv['user_id']; ?>">
                                    <?php echo htmlspecialchars($adv['full_name']); ?> (@<?php echo htmlspecialchars($adv['username']); ?>) — Level <?php echo $adv['level']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="reward_amount">Gold Amount *</label>
                        <input type="number" id="reward_amount" name="reward_amount" min="1" required placeholder="e.g., 500">
                    </div>
                    
                    <div class="form-group">
                        <label for="reward_quest_id">Associated Quest (optional)</label>
                        <select id="reward_quest_id" name="reward_quest_id">
                            <option value="">-- No Quest --</option>
                            <?php foreach ($activeQuests as $q): ?>
                                <option value="<?php echo $q['quest_id']; ?>"><?php echo htmlspecialchars($q['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="reward_description">Description *</label>
                        <input type="text" id="reward_description" name="reward_description" required maxlength="200" 
                               value="Quest Reward" placeholder="e.g., Quest Reward, Festival Bonus">
                    </div>
                    
                    <button type="submit" class="btn btn-success" onclick="return confirm('Issue this reward?');">Issue Reward</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Transaction History -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
            <h2 class="card-title" style="margin-bottom: 0;">Transaction History (<?php echo count($transactions); ?>)</h2>
            <button onclick="window.print()" class="btn btn-secondary btn-sm">Print Statement</button>
        </div>
        
        <?php if (!empty($transactions)): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Description</th>
                            <th>Quest</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rowNum = 1;
                        foreach ($transactions as $txn): 
                        ?>
                            <tr>
                                <td><?php echo $rowNum; ?></td>
                                <td>
                                    <?php 
                                    switch ($txn['type']) {
                                        case 'REWARD':
                                            echo '<span style="color: var(--color-success);">&#9650; Reward</span>';
                                            break;
                                        case 'SPEND':
                                            echo '<span style="color: var(--color-error);">&#9660; Spent</span>';
                                            break;
                                        default:
                                            echo htmlspecialchars($txn['type']);
                                    }
                                    ?>
                                </td>
                                <td style="font-weight: bold; color: <?php echo $txn['amount'] > 0 ? 'var(--color-success)' : 'var(--color-error)'; ?>">
                                    <?php echo ($txn['amount'] > 0 ? '+' : '-') . formatGold(abs($txn['amount'])); ?>
                                </td>
                                <td><?php echo htmlspecialchars($txn['description']); ?></td>
                                <td><?php echo $txn['quest_title'] ? htmlspecialchars($txn['quest_title']) : '<em style="color: var(--color-text-muted);">N/A</em>'; ?></td>
                                <td><?php echo formatDate($txn['created_at']); ?></td>
                            </tr>
                        <?php 
                            $rowNum++;
                        endforeach; 
                        ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">&#128176;</div>
                <p>No transactions yet. Complete quests to earn gold!</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Transaction Summary -->
    <div class="card" style="margin-top: 20px;">
        <h2 class="card-title">Transaction Summary</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
            <div style="text-align: center; padding: 15px; background: rgba(102, 187, 106, 0.1); border-radius: 8px; border: 1px solid rgba(102, 187, 106, 0.3);">
                <div style="font-size: 0.9rem; color: var(--color-text-muted);">Total Earned</div>
                <div style="font-size: 1.5rem; font-weight: bold; color: var(--color-success);">
                    <?php 
                    $earned = 0;
                    foreach ($transactions as $txn) { if ($txn['amount'] > 0) $earned += $txn['amount']; }
                    echo formatGold($earned);
                    ?>
                </div>
            </div>
            <div style="text-align: center; padding: 15px; background: rgba(239, 83, 80, 0.1); border-radius: 8px; border: 1px solid rgba(239, 83, 80, 0.3);">
                <div style="font-size: 0.9rem; color: var(--color-text-muted);">Total Spent</div>
                <div style="font-size: 1.5rem; font-weight: bold; color: var(--color-error);">
                    <?php 
                    $spent = 0;
                    foreach ($transactions as $txn) { if ($txn['amount'] < 0) $spent += abs($txn['amount']); }
                    echo formatGold($spent);
                    ?>
                </div>
            </div>
            <div style="text-align: center; padding: 15px; background: rgba(200, 176, 97, 0.1); border-radius: 8px; border: 1px solid rgba(200, 176, 97, 0.3);">
                <div style="font-size: 0.9rem; color: var(--color-text-muted);">Total Transactions</div>
                <div style="font-size: 1.5rem; font-weight: bold; color: var(--color-gold);">
                    <?php echo count($transactions); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
