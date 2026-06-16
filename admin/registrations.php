<?php
// registrations.php - Admin / Editor registrations verification panel

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role-check.php';

// Restricted to admin & editor for processing actions
requireLogin();
if (!in_array($_SESSION['role'], ['admin', 'editor'])) {
    checkRole(['admin', 'editor']); // triggers access denied screen
}

$page_title = "Review Registrations - BSFI Admin";
include __DIR__ . '/../includes/header.php';

$message = '';

// Handle review decisions (Approve / Reject / Archive)
if (isset($_POST['action_decision']) && isset($_POST['athlete_id'])) {
    // CSRF verification
    if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
        $message = "<div class='alert alert-danger'>Invalid CSRF Token.</div>";
    } else {
        $athleteId = (int)$_POST['athlete_id'];
        $decision = $_POST['action_decision']; // approved, rejected, archived
        
        if (in_array($decision, ['approved', 'rejected', 'archived'])) {
            try {
                $stmt = $pdo->prepare("UPDATE athletes SET status = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
                $stmt->execute([$decision, $_SESSION['user_id'], $athleteId]);
                
                // Fetch athlete details for audit logging
                $athStmt = $pdo->prepare("SELECT regn_no, full_name FROM athletes WHERE id = ?");
                $athStmt->execute([$athleteId]);
                $ath = $athStmt->fetch();
                
                logAction($pdo, "Reviewed Athlete Application", "athletes", $athleteId, "Name: {$ath['full_name']} | Decision: $decision");
                $message = "<div class='alert alert-success'>Application status for <strong>" . htmlspecialchars($ath['full_name']) . "</strong> has been updated to <strong>" . strtoupper($decision) . "</strong>.</div>";
            } catch (PDOException $e) {
                $message = "<div class='alert alert-danger'>Database update error: " . $e->getMessage() . "</div>";
            }
        }
    }
}

// Fetch pending registrations
$stmt = $pdo->query("SELECT * FROM athletes WHERE status = 'pending' ORDER BY created_at ASC");
$pendingList = $stmt->fetchAll();
?>

<div class="admin-wrapper" style="background:#08142E; min-height:95vh; padding:6rem 0; color:#FAF7F0;">
    <div class="container">
        
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:3rem; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:1.5rem;">
            <div>
                <span style="color:#24C27A; text-transform:uppercase; letter-spacing:0.05em; font-weight:600; font-size:0.9rem;">Verification Desk</span>
                <h1 style="font-family:'Outfit',sans-serif; font-size:2.5rem; font-weight:700;">Review Pending Registrations</h1>
            </div>
            <a href="dashboard.php" class="btn" style="border:1px solid rgba(255,255,255,0.15); color:#FAF7F0; border-radius:999px;">Return to Dashboard</a>
        </div>

        <?php echo $message; ?>

        <div style="display:flex; flex-direction:column; gap:2rem;">
            <?php if (count($pendingList) > 0): ?>
                <?php foreach ($pendingList as $ath): ?>
                    <div class="glass-card" style="background:rgba(22, 41, 90, 0.4); padding:2.5rem; border-radius:28px; display:grid; grid-template-columns:2fr 1fr; gap:2rem; align-items:center;">
                        
                        <!-- Details -->
                        <div>
                            <div style="display:flex; align-items:center; gap:1rem; margin-bottom:1rem;">
                                <h3 style="font-size:1.5rem; font-family:'Outfit',sans-serif;"><?php echo htmlspecialchars($ath['full_name']); ?></h3>
                                <span style="font-size:0.8rem; background:rgba(255,255,255,0.05); border:1px solid #F4B942; color:#F4B942; padding:0.2rem 0.5rem; border-radius:4px; text-transform:uppercase; font-weight:600;">Pending Verification</span>
                            </div>
                            
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; font-size:0.9rem; border-top:1px solid rgba(255,255,255,0.05); padding-top:1rem;">
                                <div><strong>Registration No:</strong> <span style="color:#24C27A; font-family:monospace;"><?php echo htmlspecialchars($ath['regn_no']); ?></span></div>
                                <div><strong>Gender:</strong> <?php echo htmlspecialchars($ath['gender']); ?></div>
                                <div><strong>DOB:</strong> <?php echo htmlspecialchars($ath['dob']); ?></div>
                                <div><strong>Classification:</strong> <?php echo htmlspecialchars($ath['classification']); ?></div>
                                <div><strong>State Association:</strong> <?php echo htmlspecialchars($ath['representing_for']); ?></div>
                                <div><strong>District:</strong> <?php echo htmlspecialchars($ath['district'] ?: 'N/A'); ?></div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div style="display:flex; flex-direction:column; gap:0.75rem; justify-content:center;">
                            <form action="registrations.php" method="POST" style="display:flex; flex-direction:column; gap:0.5rem;">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="athlete_id" value="<?php echo $ath['id']; ?>">
                                
                                <button type="submit" name="action_decision" value="approved" class="btn" style="background:#24C27A; color:#08142E; font-weight:bold; border-radius:999px; cursor:pointer; padding:0.7rem;">Approve Registration</button>
                                <button type="submit" name="action_decision" value="rejected" class="btn" style="background:#D72638; color:#fff; font-weight:bold; border-radius:999px; cursor:pointer; padding:0.7rem;">Reject Registration</button>
                                <button type="submit" name="action_decision" value="archived" class="btn" style="border:1px solid rgba(255,255,255,0.15); color:#FAF7F0; border-radius:999px; cursor:pointer; padding:0.7rem;">Archive Request</button>
                            </form>
                        </div>

                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="glass-card" style="background:rgba(22, 41, 90, 0.2); padding:4rem; border-radius:28px; text-align:center;">
                    <p style="font-size:1.1rem; opacity:0.7;">🎉 All clear! There are no pending athlete registrations to review.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
