<?php
/**
 * Registration Page
 * Adventurer Guild Registration & Quest Board
 */
$pageTitle = "Register";
require_once __DIR__ . '/includes/functions.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$errors = array();
$successMessage = "";

// Get available guilds for the form
$conn = getConnection();
$guilds = getAllGuilds($conn);
closeConnection($conn);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $fullName = sanitizeInput($_POST['full_name'] ?? '');
    $role = sanitizeInput($_POST['role'] ?? 'adventurer');
    $playerClass = sanitizeInput($_POST['class'] ?? '');
    $level = intval($_POST['level'] ?? 1);
    $bio = sanitizeInput($_POST['bio'] ?? '');
    $guildId = !empty($_POST['guild_id']) ? intval($_POST['guild_id']) : NULL;
    
    // Validation
    if (empty($username)) {
        $errors[] = "Username is required.";
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = "Username must be between 3 and 50 characters.";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!isValidEmail($email)) {
        $errors[] = "Please enter a valid email address.";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }
    
    if (empty($fullName)) {
        $errors[] = "Full name is required.";
    }
    
    // Validate guild selection
    if ($guildId !== NULL) {
        $conn = getConnection();
        $guild = getGuildById($conn, $guildId);
        if (!$guild) {
            $errors[] = "Invalid guild selection.";
        }
        closeConnection($conn);
    }
    
    // Check for existing username or email
    if (empty($errors)) {
        $conn = getConnection();
        
        // Check username
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Username is already taken.";
        }
        $stmt->close();
        
        // Check email
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Email is already registered.";
        }
        $stmt->close();
        
        if (empty($errors)) {
            // Hash the password using bcrypt
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, role, class, level, bio, guild_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssisi", $username, $email, $hashedPassword, $fullName, $role, $playerClass, $level, $bio, $guildId);
            
            if ($stmt->execute()) {
                $newUserId = $stmt->insert_id;
                
                // Create wallet for new user
                $stmt2 = $conn->prepare("INSERT INTO wallets (user_id, balance) VALUES (?, 100)");
                $stmt2->bind_param("i", $newUserId);
                $stmt2->execute();
                $stmt2->close();
                
                // Handle avatar upload if present
                if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                    $avatarFile = uploadProfilePicture($_FILES['avatar'], $newUserId);
                    if ($avatarFile) {
                        $stmt3 = $conn->prepare("UPDATE users SET avatar = ? WHERE user_id = ?");
                        $stmt3->bind_param("si", $avatarFile, $newUserId);
                        $stmt3->execute();
                        $stmt3->close();
                    }
                }
                
                $successMessage = "Registration successful! You start with 100 Gold. You can now login with your credentials.";
            } else {
                $errors[] = "Registration failed. Please try again.";
            }
            
            $stmt->close();
        }
        
        closeConnection($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> — Quest Board</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="logo">
                    <span class="logo-icon">&#9876;</span>
                    <span class="logo-text">Adventurer's Guild</span>
                </a>
                <nav class="main-nav">
                    <a href="index.php" class="nav-link">Home</a>
                    <a href="about.php" class="nav-link">About</a>
                    <a href="login.php" class="nav-link">Login</a>
                    <a href="register.php" class="nav-link btn-register active">Register</a>
                </nav>
            </div>
        </div>
    </header>
    
    <main class="main-content">
        <div class="container">
            <h1 class="page-title">Join the Guild</h1>
            <p class="page-subtitle">Create your adventurer account</p>
            
            <div class="form-container" style="max-width: 550px;">
                <div class="card">
                    <?php if (!empty($successMessage)): ?>
                        <?php displayFlashMessage($successMessage, 'success'); ?>
                        <p style="text-align: center; margin-top: 15px;">
                            <a href="login.php" class="btn btn-primary">Go to Login</a>
                        </p>
                    <?php else: ?>
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-error">
                                <strong>Registration Errors:</strong><br>
                                <?php foreach ($errors as $error): ?>
                                    &bull; <?php echo htmlspecialchars($error); ?><br>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="register.php" id="registerForm" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="username">Username *</label>
                                <input type="text" id="username" name="username" required minlength="3" maxlength="50" 
                                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                       placeholder="Enter your adventurer username">
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" required maxlength="100"
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                       placeholder="Enter your email">
                            </div>
                            
                            <div class="form-group">
                                <label for="password">Password *</label>
                                <input type="password" id="password" name="password" required minlength="6"
                                       placeholder="Minimum 6 characters">
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirm Password *</label>
                                <input type="password" id="confirm_password" name="confirm_password" required minlength="6"
                                       placeholder="Re-enter your password">
                            </div>
                            
                            <div class="form-group">
                                <label for="full_name">Full Name (Adventurer Name) *</label>
                                <input type="text" id="full_name" name="full_name" required maxlength="100"
                                       value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                                       placeholder="e.g., Aragorn the Ranger">
                            </div>
                            
                            <div class="form-group">
                                <label for="role">Role *</label>
                                <select id="role" name="role" required>
                                    <option value="adventurer" <?php echo (isset($_POST['role']) && $_POST['role'] === 'adventurer') ? 'selected' : ''; ?>>Adventurer</option>
                                    <option value="guild_master" <?php echo (isset($_POST['role']) && $_POST['role'] === 'guild_master') ? 'selected' : ''; ?>>Guild Master</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="class">Adventurer Class</label>
                                <input type="text" id="class" name="class" maxlength="50"
                                       value="<?php echo isset($_POST['class']) ? htmlspecialchars($_POST['class']) : ''; ?>"
                                       placeholder="e.g., Wizard, Warrior, Rogue">
                            </div>
                            
                            <div class="form-group">
                                <label for="guild_id">Guild Affiliation</label>
                                <select id="guild_id" name="guild_id">
                                    <option value="">-- No Guild --</option>
                                    <?php foreach ($guilds as $guild): ?>
                                        <option value="<?php echo $guild['guild_id']; ?>" 
                                                <?php echo (isset($_POST['guild_id']) && $_POST['guild_id'] == $guild['guild_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($guild['guild_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="avatar">Profile Picture</label>
                                <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp">
                                <small style="color: var(--color-text-muted);">JPG, PNG, GIF, or WebP. Max 2MB.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="level">Level</label>
                                <input type="number" id="level" name="level" min="1" max="100"
                                       value="<?php echo isset($_POST['level']) ? intval($_POST['level']) : 1; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="bio">Bio / Background</label>
                                <textarea id="bio" name="bio" placeholder="Tell us about your adventurer's background..."><?php echo isset($_POST['bio']) ? htmlspecialchars($_POST['bio']) : ''; ?></textarea>
                            </div>
                            
                            <div style="text-align: center;">
                                <button type="submit" class="btn btn-primary">Create Account</button>
                                <a href="login.php" class="btn btn-secondary" style="margin-left: 10px;">Already have an account? Login</a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <footer class="main-footer">
        <div class="container">
            <p>&copy; <?php echo date("Y"); ?> Adventurer's Guild — Quest Board System</p>
            <p class="footer-note">A CCS0043 Final Project | FEU Institute of Technology</p>
        </div>
    </footer>
</body>
</html>
