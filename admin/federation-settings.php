<?php
// federation-settings.php - Manage centralized federation payment details
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role-check.php';

// Restricted strictly to admin role
checkRole('admin');

$page_title = "Federation Settings - BSFI Admin";
include __DIR__ . '/../includes/header.php';

$message = '';

// Handle save settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
        $message = "<div class='alert alert-danger'>Invalid CSRF Token.</div>";
    } else {
        $bank_name = trim($_POST['payment_bank_name'] ?? '');
        $account_name = trim($_POST['payment_account_name'] ?? '');
        $account_number = trim($_POST['payment_account_number'] ?? '');
        $branch = trim($_POST['payment_branch'] ?? '');
        $ifsc_code = trim($_POST['payment_ifsc_code'] ?? '');

        if (empty($bank_name) || empty($account_name) || empty($account_number) || empty($branch) || empty($ifsc_code)) {
            $message = "<div class='alert alert-danger'>All bank details are required.</div>";
        } else {
            try {
                $pdo->beginTransaction();

                $settings = [
                    'payment_bank_name' => $bank_name,
                    'payment_account_name' => $account_name,
                    'payment_account_number' => $account_number,
                    'payment_branch' => $branch,
                    'payment_ifsc_code' => $ifsc_code
                ];

                $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                foreach ($settings as $key => $value) {
                    $stmt->execute([$key, $value, $value]);
                }

                $pdo->commit();
                logAction($pdo, "Updated Central Federation Bank Details");
                $message = "<div class='alert alert-success'>Federation settings updated successfully.</div>";
            } catch (PDOException $e) {
                $pdo->rollBack();
                $message = "<div class='alert alert-danger'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
    }
}

// Fetch current settings
$stmt = $pdo->query("SELECT * FROM site_settings WHERE setting_key LIKE 'payment_%'");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$current_settings = [];
foreach ($rows as $row) {
    $current_settings[$row['setting_key']] = $row['setting_value'];
}
?>

<div class="admin-wrapper">
    <div class="container-fluid" style="padding: 2rem;">
        
        <div class="admin-page-title-row" style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <span class="admin-section-eyebrow">Federation Configurations</span>
                <h1 class="admin-page-title" style="margin: 0; font-family: 'Outfit', sans-serif; font-weight: 800; color: #081B4B;">Federation Settings</h1>
            </div>
            <a href="dashboard.php" class="admin-btn admin-btn-outline" style="text-decoration: none;">Return to Dashboard</a>
        </div>

        <?php if (!empty($message)) echo $message; ?>

        <div class="admin-card" style="background: #ffffff; border-radius: 16px; padding: 2rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
            <h3 style="font-family: 'Outfit', sans-serif; color: #081B4B; font-weight: 700; margin-bottom: 1.5rem;">Centralized Payment Details</h3>
            <p style="color: #64748b; font-size: 0.95rem; margin-bottom: 2rem;">Configure the official federation bank account. These details will automatically be shown to members during event registration checkout when a fee is configured.</p>

            <form action="federation-settings.php" method="POST" style="display: flex; flex-direction: column; gap: 1.5rem; max-width: 800px;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="save_settings" value="1">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="payment_bank_name" style="font-weight: 600; color: #1e293b; display: block; margin-bottom: 0.5rem;">Bank Name <span style="color: var(--danger);">*</span></label>
                        <input type="text" id="payment_bank_name" name="payment_bank_name" class="admin-input" required value="<?php echo htmlspecialchars($current_settings['payment_bank_name'] ?? ''); ?>" placeholder="e.g. State Bank of India" style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid #cbd5e1;">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="payment_account_name" style="font-weight: 600; color: #1e293b; display: block; margin-bottom: 0.5rem;">Account Name <span style="color: var(--danger);">*</span></label>
                        <input type="text" id="payment_account_name" name="payment_account_name" class="admin-input" required value="<?php echo htmlspecialchars($current_settings['payment_account_name'] ?? ''); ?>" placeholder="e.g. Boccia Sports Federation of India" style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid #cbd5e1;">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="payment_account_number" style="font-weight: 600; color: #1e293b; display: block; margin-bottom: 0.5rem;">Account Number <span style="color: var(--danger);">*</span></label>
                        <input type="text" id="payment_account_number" name="payment_account_number" class="admin-input" required value="<?php echo htmlspecialchars($current_settings['payment_account_number'] ?? ''); ?>" placeholder="e.g. 36123404464" style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid #cbd5e1;">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="payment_ifsc_code" style="font-weight: 600; color: #1e293b; display: block; margin-bottom: 0.5rem;">IFSC Code <span style="color: var(--danger);">*</span></label>
                        <input type="text" id="payment_ifsc_code" name="payment_ifsc_code" class="admin-input" required value="<?php echo htmlspecialchars($current_settings['payment_ifsc_code'] ?? ''); ?>" placeholder="e.g. SBIN0019158" style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid #cbd5e1;">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="payment_branch" style="font-weight: 600; color: #1e293b; display: block; margin-bottom: 0.5rem;">Branch Address <span style="color: var(--danger);">*</span></label>
                    <textarea id="payment_branch" name="payment_branch" class="admin-input" required rows="3" placeholder="e.g. Saggu Complex, 100 Feet Road, Near Aakash Institute, Bathinda, Punjab - 151001" style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid #cbd5e1;"><?php echo htmlspecialchars($current_settings['payment_branch'] ?? ''); ?></textarea>
                </div>

                <div style="margin-top: 1rem;">
                    <button type="submit" class="admin-btn admin-btn-primary" style="padding: 0.75rem 2rem; border-radius: 8px; font-weight: 700;">Save Changes</button>
                </div>
            </form>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
