<?php
// import-athletes.php - Administrative CSV registration uploader tool

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role-check.php';

// Restricted to Admin role
checkRole('admin');

$page_title = "Bulk Import Athletes - BSFI Admin";
include __DIR__ . '/../includes/header.php';

$message = '';
$preview_data = [];
$validation_errors = [];
$temp_file = isset($_POST['temp_file']) ? $_POST['temp_file'] : '';

// Handle CSV File Upload
if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == UPLOAD_ERR_OK) {
    $file_type = pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION);
    if (strtolower($file_type) !== 'csv') {
        $message = "<div class='alert alert-danger'>Invalid file type. Please upload a valid CSV file.</div>";
    } else {
        // Create imports temp folder
        $target_dir = __DIR__ . '/../uploads/temp/';
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $temp_filename = uniqid('csv_', true) . '.csv';
        $target_file = $target_dir . $temp_filename;
        
        if (move_uploaded_file($_FILES['csv_file']['tmp_name'], $target_file)) {
            $temp_file = $temp_filename;
            
            // Read CSV for preview
            if (($handle = fopen($target_file, "r")) !== FALSE) {
                // Get header row
                $headers = fgetcsv($handle, 1000, ",");
                $row_idx = 0;
                while (($row = fgetcsv($handle, 1000, ",")) !== FALSE && $row_idx < 10) {
                    if (count($row) >= 5) {
                        $preview_data[] = $row;
                    }
                    $row_idx++;
                }
                fclose($handle);
            }
        } else {
            $message = "<div class='alert alert-danger'>Failed to save uploaded file.</div>";
        }
    }
}

// Handle Database Transaction Save
if (isset($_POST['confirm_import']) && !empty($temp_file)) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
        $message = "<div class='alert alert-danger'>Invalid CSRF Token.</div>";
    } else {
        $target_file = __DIR__ . '/../uploads/temp/' . $temp_file;
        if (file_exists($target_file)) {
            try {
                $pdo->beginTransaction();
                
                $handle = fopen($target_file, "r");
                $headers = fgetcsv($handle, 1000, ",");
                $headers = array_map('trim', $headers);
                
                // Track counts
                $inserted = 0;
                $duplicates = 0;
                $line_number = 1;
                
                while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $line_number++;
                    if (count($row) < 5) continue;
                    
                    // Simple index-based mapping
                    $org_code = isset($row[0]) ? trim($row[0]) : '';
                    $org_name = isset($row[1]) ? trim($row[1]) : '';
                    $regn_no = isset($row[4]) ? trim($row[4]) : '';
                    $rroll = isset($row[5]) ? trim($row[5]) : '';
                    $full_name = isset($row[6]) ? trim($row[6]) : '';
                    $gender = isset($row[7]) ? strtoupper(trim($row[7])) : 'MALE';
                    $dob_raw = isset($row[8]) ? trim($row[8]) : '';
                    $cert_no = isset($row[15]) ? trim($row[15]) : '';
                    $state = isset($row[16]) ? trim($row[16]) : '';
                    $classification = isset($row[24]) ? trim($row[24]) : '';
                    $representing_for = isset($row[26]) ? trim($row[26]) : '';
                    $wheelchair_status = isset($row[12]) ? trim($row[12]) : '';
                    
                    // Normalize gender
                    if ($gender !== 'MALE' && $gender !== 'FEMALE') {
                        $gender = 'OTHER';
                    }
                    
                    // Format date
                    $dob = date('Y-m-d', strtotime($dob_raw));
                    if (!$dob || $dob === '1970-01-01') {
                        $dob = '2000-01-01';
                    }
                    
                    // Check duplicate registration
                    $dupCheck = $pdo->prepare("SELECT id FROM athletes WHERE regn_no = ?");
                    $dupCheck->execute([$regn_no]);
                    if ($dupCheck->fetch()) {
                        $duplicates++;
                        continue;
                    }
                    
                    // Insert
                    $stmt = $pdo->prepare("INSERT INTO athletes (regn_no, full_name, gender, dob, state, classification, representing_for, wheelchair_status, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'approved', ?)");
                    $stmt->execute([$regn_no, $full_name, $gender, $dob, $state, $classification, $representing_for, $wheelchair_status, $_SESSION['user_id']]);
                    $inserted++;
                }
                
                fclose($handle);
                $pdo->commit();
                
                // Log action
                logAction($pdo, "CSV Athletes Import Completed", "athletes", null, "Imported: $inserted, Duplicates skipped: $duplicates");
                
                // Clean up file
                unlink($target_file);
                
                $message = "<div class='alert alert-success'><strong>Import Summary:</strong> Successfully imported $inserted athletes. Skipped $duplicates duplicate records.</div>";
                $temp_file = ''; // clear state
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = "<div class='alert alert-danger'>Transaction failed: " . $e->getMessage() . "</div>";
            }
        } else {
            $message = "<div class='alert alert-danger'>Session file lost. Please upload your CSV again.</div>";
        }
    }
}
?>

<div class="admin-wrapper" style="background:#08142E; min-height:80vh; padding:6rem 0; color:#FAF7F0;">
    <div class="container">
        
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:3rem;">
            <div>
                <span style="color:#24C27A; text-transform:uppercase; letter-spacing:0.05em; font-weight:600; font-size:0.9rem;">Database Tools</span>
                <h1 style="font-family:'Outfit',sans-serif; font-size:2.5rem; font-weight:700;">Bulk Import Athletes</h1>
            </div>
            <a href="../admin/dashboard.php" class="btn" style="border:1px solid rgba(255,255,255,0.2); color:#FAF7F0; border-radius:999px;">Return to Dashboard</a>
        </div>

        <?php echo $message; ?>

        <!-- Step 1: Upload File -->
        <?php if (empty($temp_file)): ?>
            <div class="glass-card" style="background:rgba(22, 41, 90, 0.5); padding:3rem; border-radius:28px;">
                <form action="import-athletes.php" method="POST" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:2rem;">
                    <div>
                        <h3 style="font-size:1.4rem; color:#FAF7F0; margin-bottom:0.5rem;">Select Athlete Registry CSV</h3>
                        <p style="color:#FAF7F0; opacity:0.75; font-size:0.95rem;">Select your database CSV file formatted for registration loading. Existing records matching unique Registration Numbers will be skipped automatically.</p>
                    </div>
                    
                    <div style="border:2px dashed rgba(36, 194, 122, 0.4); border-radius:12px; padding:3rem; text-align:center; background:rgba(0,0,0,0.2);">
                        <input type="file" name="csv_file" accept=".csv" required style="font-size:1rem; cursor:pointer; color:#FAF7F0;">
                    </div>
                    
                    <button type="submit" class="btn" style="align-self:flex-start; background:#24C27A; color:#08142E; font-weight:bold; padding:0.8rem 2.5rem; border-radius:999px; cursor:pointer;">Upload and Preview</button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Step 2: Preview & Confirm -->
        <?php if (!empty($temp_file) && !empty($preview_data)): ?>
            <div class="glass-card" style="background:rgba(22, 41, 90, 0.5); padding:3rem; border-radius:28px;">
                <h3 style="font-size:1.5rem; margin-bottom:1rem;">CSV File Preview (First 10 Rows)</h3>
                <p style="color:#FAF7F0; opacity:0.8; margin-bottom:2rem;">Verify headers mapping correctly. Click "Commit Import" below to run the transaction-based load.</p>
                
                <div style="overflow-x:auto; margin-bottom:2rem; border-radius:12px; border:1px solid rgba(255,255,255,0.1);">
                    <table class="doc-table" style="width:100%; border-collapse:collapse; text-align:left;">
                        <thead>
                            <tr style="background:rgba(255,255,255,0.05);">
                                <th style="padding:1rem;">Index</th>
                                <th style="padding:1rem;">Name</th>
                                <th style="padding:1rem;">Gender</th>
                                <th style="padding:1rem;">Reg No</th>
                                <th style="padding:1rem;">State</th>
                                <th style="padding:1rem;">Classification</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($preview_data as $idx => $row): ?>
                                <tr style="border-bottom:1px solid rgba(255,255,255,0.05);">
                                    <td style="padding:1rem; font-family:monospace;"><?php echo $idx + 1; ?></td>
                                    <td style="padding:1rem; font-weight:bold;"><?php echo htmlspecialchars($row[6] ?? 'N/A'); ?></td>
                                    <td style="padding:1rem;"><?php echo htmlspecialchars($row[7] ?? 'N/A'); ?></td>
                                    <td style="padding:1rem; font-family:monospace; color:#24C27A;"><?php echo htmlspecialchars($row[4] ?? 'N/A'); ?></td>
                                    <td style="padding:1rem;"><?php echo htmlspecialchars($row[16] ?? 'N/A'); ?></td>
                                    <td style="padding:1rem;"><?php echo htmlspecialchars($row[24] ?? 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <form action="import-athletes.php" method="POST" style="display:flex; gap:1rem;">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="temp_file" value="<?php echo htmlspecialchars($temp_file); ?>">
                    
                    <button type="submit" name="confirm_import" class="btn" style="background:#24C27A; color:#08142E; font-weight:bold; padding:0.8rem 2.5rem; border-radius:999px; cursor:pointer;">Commit Import</button>
                    <a href="import-athletes.php" class="btn" style="border:1px solid rgba(255,255,255,0.2); color:#FAF7F0; padding:0.8rem 2.5rem; border-radius:999px; text-decoration:none;">Cancel</a>
                </form>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
