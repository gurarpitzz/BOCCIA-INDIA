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

<div class="admin-wrapper">
    <div class="container-fluid" style="padding: 2rem;">
        
        <div class="admin-page-title-row">
            <div>
                <span class="admin-section-eyebrow">Federation Security</span>
                <h1 class="admin-page-title">Manage Staff Accounts</h1>
            </div>
            <a href="dashboard.php" class="admin-btn admin-btn-outline">Return to Dashboard</a>
        </div>

        <?php if (!empty($message)) echo $message; ?>

        <div style="display:grid; grid-template-columns:1.2fr 2fr; gap:2.5rem; align-items: start;">
            
            <!-- Left Side: Add Form -->
            <div>
                <div class="admin-card">
                    <h3 class="admin-card-title">Register Staff Account</h3>
                    <p class="admin-card-desc">Create administrative and editor access credentials.</p>
                    <form action="users.php" method="POST" style="display:flex; flex-direction:column; gap:1rem;">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="admin-form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" class="admin-input" required placeholder="Staff identifier">
                        </div>
                        
                        <div class="admin-form-group">
                            <label for="password">Temporary Password</label>
                            <input type="password" id="password" name="password" class="admin-input" required placeholder="Secure password">
                        </div>
                        
                        <div class="admin-form-group">
                            <label for="role">System Access Role</label>
                            <select id="role" name="role" class="admin-select" required>
                                <option value="viewer">Viewer (Read-only Dashboards)</option>
                                <option value="editor">Editor (View/Edit News, Events, Athletes)</option>
                                <option value="admin">Administrator (Full System Control)</option>
                            </select>
                        </div>

                        <button type="submit" name="create_user" class="admin-btn admin-btn-primary" style="width: 100%; margin-top: 0.5rem;">Create Account</button>
                    </form>
                </div>
            </div>

            <!-- Right Side: User List -->
            <div class="admin-card">
                <h3 class="admin-card-title">Registered System Accounts</h3>
                <p class="admin-card-desc">Accounts with login privileges for this administrative panel.</p>
                
                <div class="admin-table-wrapper">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staffList as $user): ?>
                                <tr>
                                    <td style="font-weight:bold;"><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td>
                                        <span class="admin-badge <?php echo ($user['role'] === 'admin') ? 'admin-badge-success' : (($user['role'] === 'editor') ? 'admin-badge-warning' : 'admin-badge-info'); ?>">
                                            <?php echo htmlspecialchars($user['role']); ?>
                                        </span>
                                    </td>
                                    <td style="color: var(--text-muted);"><?php echo htmlspecialchars($user['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
