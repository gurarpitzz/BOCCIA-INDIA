<?php
// login.php - Secure portal login gate

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$error = '';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header("Location: admin/dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $token = $_POST['csrf_token'] ?? '';
    
    // Validate CSRF
    if (!validateCSRF($token)) {
        $error = "Invalid security token. Please try again.";
    } elseif (empty($username) || empty($password)) {
        $error = "Please fill in all credentials.";
    } else {
        try {
            // Retrieve user
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Login Success!
                regenerateUserSession();
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // Write audit log
                logAction($pdo, "User Logged In Successfully");
                
                header("Location: admin/dashboard.php");
                exit();
            } else {
                // Invalid login
                $error = "Invalid username or password.";
                // Simple artificial delay to mitigate brute force
                usleep(500000); // 0.5s
            }
        } catch (PDOException $e) {
            $error = "Database failure: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login - Boccia India</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body style="background:#08142E; color:#FAF7F0; display:flex; flex-direction:column; justify-content:center; align-items:center; min-height:100vh; font-family:'Poppins', sans-serif; margin:0;">

    <div style="margin-bottom:2rem; text-align:center;">
        <img src="boccia-india-logo.webp" alt="BSFI Logo" style="height:80px; margin-bottom:1rem;">
        <h2 style="font-family:'Outfit', sans-serif; font-size:1.8rem; font-weight:700;">Boccia Sports Federation of India</h2>
        <p style="opacity:0.7; font-size:0.9rem;">Official Staff & Administration Portal</p>
    </div>

    <div class="glass-card" style="background:rgba(22, 41, 90, 0.5); padding:3rem; border-radius:28px; width:90%; max-width:420px; border:1px solid rgba(255,255,255,0.1); box-shadow:0 20px 40px rgba(0,0,0,0.5);">
        <h3 style="font-family:'Outfit', sans-serif; font-size:1.5rem; margin-bottom:1.5rem; text-align:center;">Sign In</h3>
        
        <?php if (!empty($error)): ?>
            <div style="background:rgba(215, 38, 56, 0.15); border:1px solid #D72638; color:#fff; padding:0.85rem; border-radius:8px; margin-bottom:1.5rem; font-size:0.9rem; text-align:center;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" style="display:flex; flex-direction:column; gap:1.25rem;">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="form-group">
                <label for="login-username" style="font-size:0.8rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; opacity:0.8;">Username</label>
                <input type="text" id="login-username" name="username" class="form-input" required placeholder="Enter username" style="background:rgba(0,0,0,0.2);">
            </div>
            
            <div class="form-group">
                <label for="login-password" style="font-size:0.8rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; opacity:0.8;">Password</label>
                <input type="password" id="login-password" name="password" class="form-input" required placeholder="Enter password" style="background:rgba(0,0,0,0.2);">
            </div>
            
            <button type="submit" class="btn" style="background:#24C27A; color:#08142E; font-weight:bold; font-size:1rem; padding:0.9rem; border-radius:999px; cursor:pointer; margin-top:0.75rem; border:none; text-transform:uppercase; letter-spacing:0.05em;">Access Account</button>
        </form>
    </div>

    <div style="margin-top:2rem;">
        <a href="index.php" style="color:#24C27A; text-decoration:none; font-size:0.9rem; font-weight:500;">← Back to Public Website</a>
    </div>

</body>
</html>
