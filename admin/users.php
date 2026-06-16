<?php
// users.php - Admin staff management portal

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role-check.php';

// Restricted strictly to admin role
checkRole('admin');

$page_title = "Manage Staff - BSFI Admin";
include __DIR__ . '/../includes/header.php';

$message = '';

// Handle creating user
if (isset($_POST['create_user'])) {
    if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
         $message = "<div class='alert alert-danger'>Invalid CSRF Token.</div>";
    } else {
         $username = trim($_POST['username']);
         $password = trim($_POST['password']);
         $role = $_POST['role'];
         
         if (!empty($username) && !empty($password) && in_array($role, ['admin', 'editor', 'viewer'])) {
             try {
                 // Securely hash password
                 $hashed = password_hash($password, PASSWORD_DEFAULT);
                 
                 $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)");
                 $stmt->execute([$username, $hashed, $role]);
                 $newUserId = $pdo->lastInsertId();
                 
                 logAction($pdo, "Created Staff User Account", "users", $newUserId, "Username: $username | Role: $role");
                 $message = "<div class='alert alert-success'>Staff account for <strong>" . htmlspecialchars($username) . "</strong> created successfully.</div>";
             } catch (PDOException $e) {
                 if ($e->getCode() == 23000) {
                     $message = "<div class='alert alert-danger'>Username already exists. Please choose a different name.</div>";
                 } else {
                     $message = "<div class='alert alert-danger'>Database error: " . $e->getMessage() . "</div>";
                 }
             }
         } else {
             $message = "<div class='alert alert-danger'>All fields are required.</div>";
         }
    }
}

// Fetch staff list
$stmt = $pdo->query("SELECT id, username, role, created_at FROM users ORDER BY created_at DESC");
$staffList = $stmt->fetchAll();
?>

<div class="admin-wrapper" style="background:#08142E; min-height:95vh; padding:6rem 0; color:#FAF7F0;">
    <div class="container">
        
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:3rem; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:1.5rem;">
            <div>
                <span style="color:#24C27A; text-transform:uppercase; letter-spacing:0.05em; font-weight:600; font-size:0.9rem;">Federation Security</span>
                <h1 style="font-family:'Outfit',sans-serif; font-size:2.5rem; font-weight:700;">Manage Staff Accounts</h1>
            </div>
            <a href="dashboard.php" class="btn" style="border:1px solid rgba(255,255,255,0.15); color:#FAF7F0; border-radius:999px;">Return to Dashboard</a>
        </div>

        <?php echo $message; ?>

        <div style="display:grid; grid-template-columns:1.2fr 2fr; gap:3rem;">
            
            <!-- Left Side: Add Form -->
            <div>
                <div class="glass-card" style="background:rgba(22, 41, 90, 0.4); padding:2rem; border-radius:28px;">
                    <h3 style="font-size:1.3rem; margin-bottom:1.5rem; font-family:'Outfit',sans-serif;">Register Staff Account</h3>
                    <form action="users.php" method="POST" style="display:flex; flex-direction:column; gap:1.25rem;">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="form-group">
                            <label for="username" style="font-size:0.8rem; font-weight:600;">Username</label>
                            <input type="text" id="username" name="username" class="form-input" required placeholder="Staff identifier">
                        </div>
                        
                        <div class="form-group">
                            <label for="password" style="font-size:0.8rem; font-weight:600;">Temporary Password</label>
                            <input type="password" id="password" name="password" class="form-input" required placeholder="Secure password">
                        </div>
                        
                        <div class="form-group">
                            <label for="role" style="font-size:0.8rem; font-weight:600;">System Access Role</label>
                            <select id="role" name="role" class="select-input" required>
                                <option value="viewer">Viewer (Read-only Dashboards)</option>
                                <option value="editor">Editor (View/Edit News, Events, Athletes)</option>
                                <option value="admin">Administrator (Full System Control)</option>
                            </select>
                        </div>

                        <button type="submit" name="create_user" class="btn" style="background:#24C27A; color:#08142E; font-weight:bold; padding:0.85rem; border-radius:999px; cursor:pointer; margin-top:0.5rem;">Create Account</button>
                    </form>
                </div>
            </div>

            <!-- Right Side: User List -->
            <div class="glass-card" style="background:rgba(22, 41, 90, 0.4); padding:2.5rem; border-radius:28px; overflow-x:auto;">
                <h3 style="font-size:1.3rem; margin-bottom:1.5rem; font-family:'Outfit',sans-serif;">Registered System Accounts</h3>
                
                <table class="doc-table" style="width:100%; border-collapse:collapse; text-align:left; font-size:0.9rem;">
                    <thead>
                        <tr style="border-bottom:1px solid rgba(255,255,255,0.08);">
                            <th style="padding:0.75rem;">Username</th>
                            <th style="padding:0.75rem;">Role</th>
                            <th style="padding:0.75rem;">Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staffList as $user): ?>
                            <tr style="border-bottom:1px solid rgba(255,255,255,0.05);">
                                <td style="padding:0.75rem; font-weight:bold;"><?php echo htmlspecialchars($user['username']); ?></td>
                                <td style="padding:0.75rem;">
                                    <span style="font-size:0.8rem; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.15); padding:0.25rem 0.5rem; border-radius:4px; text-transform:uppercase;">
                                        <?php echo htmlspecialchars($user['role']); ?>
                                    </span>
                                </td>
                                <td style="padding:0.75rem; opacity:0.7;"><?php echo htmlspecialchars($user['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
