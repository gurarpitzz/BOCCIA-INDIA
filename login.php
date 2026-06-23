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
    <!-- Bootstrap 5.3 (Local) -->
    <link href="assets/vendor/bootstrap/bootstrap.min.css?v=1" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: url('login bg.png?v=2') center center / cover no-repeat fixed;
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container-wrapper {
            display: flex;
            width: 100%;
            max-width: 1200px;
            padding: 2rem;
            align-items: center;
            justify-content: space-between;
            gap: 4rem;
        }

        .login-left-container {
            flex: 1;
            display: flex;
            justify-content: flex-start;
            align-items: center;
        }

        .branding-logos-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 24px;
            padding: 2.25rem 2.5rem;
            display: flex;
            align-items: center;
            gap: 1.75rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(10px);
        }

        .login-brand-logo {
            height: 75px;
            width: auto;
            object-fit: contain;
        }

        .brand-sep {
            width: 1.5px;
            height: 50px;
            background: rgba(8, 27, 75, 0.15);
        }
        
        .login-card-container {
            background: #ffffff;
            border-radius: 16px;
            width: 100%;
            max-width: 440px;
            padding: 3.5rem 3rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(0, 0, 0, 0.08);
            text-align: center;
            flex-shrink: 0;
        }

        .login-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 0.25rem;
        }

        .login-subtitle {
            font-size: 0.85rem;
            color: #718096;
            margin-bottom: 2rem;
        }

        .form-control-custom {
            height: 52px;
            border-radius: 8px;
            border: 1px solid #cbd5e0;
            font-size: 0.95rem;
            padding: 0 1rem;
            width: 100%;
            margin-bottom: 1.25rem;
            color: #2d3748;
            background-color: #ffffff;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control-custom:focus {
            outline: none;
            border-color: #1a73e8;
            box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.15);
        }

        .btn-login-blue {
            background-color: #1a73e8;
            color: #ffffff;
            font-weight: 600;
            font-size: 0.95rem;
            height: 52px;
            border-radius: 8px;
            border: none;
            width: 100%;
            transition: background-color 0.2s;
            margin-top: 0.5rem;
        }
        .btn-login-blue:hover {
            background-color: #155cb8;
        }

        .return-link {
            display: inline-block;
            margin-top: 2rem;
            color: #1a73e8;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: color 0.2s;
        }
        .return-link:hover {
            color: #155cb8;
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 991px) {
            .login-container-wrapper {
                flex-direction: column;
                justify-content: center;
                gap: 2rem;
                padding: 1.5rem;
            }
            .login-left-container {
                justify-content: center;
                width: 100%;
            }
            .branding-logos-card {
                padding: 1.25rem 1.5rem;
                gap: 1.25rem;
                width: 100%;
                justify-content: center;
            }
            .login-brand-logo {
                height: 50px;
            }
            .brand-sep {
                height: 35px;
            }
        }
    </style>
</head>
<body>

    <div class="login-container-wrapper">
        <div class="login-left-container">
            <div class="branding-logos-card">
                <img src="Ministry_of_Youth_Affairs_and_Sports.svg" alt="MYAS" class="login-brand-logo myas-brand">
                <div class="brand-sep"></div>
                <img src="boccia-india-logo.webp" alt="Boccia India" class="login-brand-logo bsfi-brand">
                <div class="brand-sep"></div>
                <img src="Full Logo World Boccia.webp" alt="World Boccia" class="login-brand-logo world-brand">
            </div>
        </div>

        <div class="login-card-container">
            <h3 class="login-title">Sign In</h3>
            <p class="login-subtitle">With Your Credentials</p>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger p-2 mb-3" style="font-size:0.85rem; border-radius:8px;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <input type="text" id="login-username" name="username" class="form-control-custom" required placeholder="User">
                <input type="password" id="login-password" name="password" class="form-control-custom" required placeholder="Password">
                
                <button type="submit" class="btn-login-blue">Login</button>
            </form>

            <div>
                <a href="index.php" class="return-link">Return to Home Page</a>
            </div>
        </div>
    </div>
</body>
</html>
