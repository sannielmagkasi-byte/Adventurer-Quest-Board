<?php
/**
 * Login Page
 * Adventurer Guild Registration & Quest Board
 */
$pageTitle = "Login";
require_once __DIR__ . '/includes/functions.php';

// Start session
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$errors = array();
$successMessage = "";

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']) ? true : false;
    
    // Validation
    if (empty($username)) {
        $errors[] = "Username is required.";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required.";
    }
    
    if (empty($errors)) {
        $conn = getConnection();
        
        // Prepare statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT user_id, username, email, password, full_name, role, class, level FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password using bcrypt
            if (password_verify($password, $user['password'])) {
                // Password is correct, start session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['class'] = $user['class'];
                $_SESSION['level'] = $user['level'];
                $_SESSION['guild_id'] = $user['guild_id'] ?? null;
                $_SESSION['login_time'] = time();
                
                // Set "Remember Me" cookie if checked (expires in 30 days)
                if ($rememberMe) {
                    $cookieValue = $user['user_id'] . ':' . bin2hex(random_bytes(16));
                    setcookie('guild_remember', $cookieValue, time() + (86400 * 30), "/");
                }
                
                $stmt->close();
                closeConnection($conn);
                
                // Redirect to dashboard
                header("Location: dashboard.php");
                exit();
            } else {
                $errors[] = "Invalid password. Please try again.";
            }
        } else {
            $errors[] = "No account found with that username or email.";
        }
        
        $stmt->close();
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
                    <a href="login.php" class="nav-link active">Login</a>
                    <a href="register.php" class="nav-link btn-register">Register</a>
                </nav>
            </div>
        </div>
    </header>
    
    <main class="main-content">
        <div class="container">
            <h1 class="page-title">Enter the Guild</h1>
            <p class="page-subtitle">Sign in to access the quest board</p>
            
            <div class="form-container">
                <div class="card">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-error">
                            <strong>Login Error:</strong><br>
                            <?php foreach ($errors as $error): ?>
                                &bull; <?php echo htmlspecialchars($error); ?><br>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($successMessage)): ?>
                        <?php displayFlashMessage($successMessage, 'success'); ?>
                    <?php endif; ?>
                    
                    <form method="POST" action="login.php" id="loginForm">
                        <div class="form-group">
                            <label for="username">Username or Email *</label>
                            <input type="text" id="username" name="username" required
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                   placeholder="Enter your username or email" autofocus>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password *</label>
                            <input type="password" id="password" name="password" required
                                   placeholder="Enter your password">
                        </div>
                        
                        <div class="form-group" style="display: flex; align-items: center; gap: 10px;">
                            <input type="checkbox" id="remember_me" name="remember_me" value="1"
                                   <?php echo isset($_POST['remember_me']) ? 'checked' : ''; ?>>
                            <label for="remember_me" style="margin-bottom: 0; font-weight: normal;">Remember me (30 days)</label>
                        </div>
                        
                        <div style="text-align: center; margin-top: 25px;">
                            <button type="submit" class="btn btn-primary">Sign In</button>
                            <a href="register.php" class="btn btn-secondary" style="margin-left: 10px;">Create Account</a>
                        </div>
                    </form>
                    
                    <hr style="border-color: var(--color-border); margin: 25px 0;">
                    
                    <p style="text-align: center; color: var(--color-text-muted);">
                        Don't have an account? <a href="register.php">Register here</a> to join the Guild.
                    </p>
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
