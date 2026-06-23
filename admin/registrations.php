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
$message = '';
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'athletes';

// Process Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
        $message = "<div class='alert alert-danger border-0 p-3 mb-4 rounded-3' style='background-color:#FEF2F2; color:#991B1B;'>Invalid CSRF Token.</div>";
    } else {
        $action = $_POST['action']; // 'approve_new', 'approve_link', 'reject', 'approve_update', 'reject_update'
        $type = $_POST['type'] ?? ''; // 'athlete' or 'official'
        $applicationId = (int)($_POST['application_id'] ?? 0);
        $requestId = (int)($_POST['request_id'] ?? 0);

        try {
            if ($action === 'approve_update' || $action === 'reject_update') {
                // Fetch request details
                $reqStmt = $pdo->prepare("SELECT * FROM profile_update_requests WHERE id = ?");
                $reqStmt->execute([$requestId]);
                $req = $reqStmt->fetch(PDO::FETCH_ASSOC);

                if (!$req) {
                    throw new Exception("Profile update request not found.");
                }

                $notes = isset($_POST['review_notes']) ? trim($_POST['review_notes']) : '';
                $memberId = (int)$req['member_id'];
                $memberType = $req['member_type'];

                if ($action === 'reject_update') {
                    $up = $pdo->prepare("UPDATE profile_update_requests SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW(), review_notes = ? WHERE id = ?");
                    $up->execute([$_SESSION['user_id'], $notes, $requestId]);
                    
                    logAction($pdo, "Rejected Profile Update Request", "profile_update_requests", $requestId, "Type: $memberType | ID: $memberId");
                    $message = "<div class='alert alert-success border-0 p-3 mb-4 rounded-3' style='background-color:#ECFDF5; color:#065F46;'>Profile update request rejected successfully.</div>";
                } elseif ($action === 'approve_update') {
                    $pdo->beginTransaction();

                    if ($memberType === 'athlete') {
                        // Update live athlete details
                        $upAthlete = $pdo->prepare("UPDATE athletes SET 
                            email = COALESCE(NULLIF(?, ''), email),
                            mobile = COALESCE(NULLIF(?, ''), mobile),
                            district = COALESCE(NULLIF(?, ''), district),
                            photo_path = COALESCE(NULLIF(?, ''), photo_path),
                            photo_status = IF(? != '', 'verified', photo_status)
                            WHERE id = ?");
                        
                        $upAthlete->execute([
                            $req['requested_email'],
                            $req['requested_phone'],
                            $req['requested_pincode'],
                            $req['requested_photo_path'],
                            $req['requested_photo_path'],
                            $memberId
                        ]);
                    } else {
                        // Update live official details
                        $upOfficial = $pdo->prepare("UPDATE officials SET 
                            email = COALESCE(NULLIF(?, ''), email),
                            phone = COALESCE(NULLIF(?, ''), phone),
                            address = COALESCE(NULLIF(?, ''), address),
                            pincode = COALESCE(NULLIF(?, ''), pincode),
                            photo_path = COALESCE(NULLIF(?, ''), photo_path),
                            photo_status = IF(? != '', 'verified', photo_status)
                            WHERE id = ?");
                        
                        $upOfficial->execute([
                            $req['requested_email'],
                            $req['requested_phone'],
                            $req['requested_address'],
                            $req['requested_pincode'],
                            $req['requested_photo_path'],
                            $req['requested_photo_path'],
                            $memberId
                        ]);
                    }

                    // Update request status
                    $upReq = $pdo->prepare("UPDATE profile_update_requests SET status = 'approved', reviewed_by = ?, reviewed_at = NOW(), review_notes = ? WHERE id = ?");
                    $upReq->execute([$_SESSION['user_id'], $notes, $requestId]);

                    $pdo->commit();

                    logAction($pdo, "Approved Profile Update Request", "profile_update_requests", $requestId, "Type: $memberType | ID: $memberId");
                    $message = "<div class='alert alert-success border-0 p-3 mb-4 rounded-3' style='background-color:#ECFDF5; color:#065F46;'>Profile update request approved and applied successfully.</div>";
                }
            } elseif ($type === 'athlete') {
                // Fetch application details
                $appStmt = $pdo->prepare("SELECT * FROM athlete_applications WHERE id = ?");
                $appStmt->execute([$applicationId]);
                $app = $appStmt->fetch(PDO::FETCH_ASSOC);

                if (!$app) {
                    throw new Exception("Athlete application not found.");
                }

                if ($action === 'reject') {
                    $up = $pdo->prepare("UPDATE athlete_applications SET status = 'rejected' WHERE id = ?");
                    $up->execute([$applicationId]);
                    logAction($pdo, "Rejected Athlete Application", "athlete_applications", $applicationId, "Name: {$app['full_name']}");
                    $message = "<div class='alert alert-success border-0 p-3 mb-4 rounded-3' style='background-color:#ECFDF5; color:#065F46;'>Application for " . htmlspecialchars($app['full_name']) . " rejected successfully.</div>";
                } elseif ($action === 'approve_link') {
                    $existingId = (int)$_POST['existing_id'];
                    $existStmt = $pdo->prepare("SELECT * FROM athletes WHERE id = ?");
                    $existStmt->execute([$existingId]);
                    $existing = $existStmt->fetch(PDO::FETCH_ASSOC);

                    if (!$existing) {
                        throw new Exception("Existing athlete profile not found for linking.");
                    }

                    // Link action copies photo path and marks as verified
                    $upAthlete = $pdo->prepare("UPDATE athletes SET 
                        full_name = ?, gender = ?, dob = ?, mobile = ?, email = ?, 
                        state = ?, district = ?, classification = ?, wheelchair_status = ?, 
                        photo_path = COALESCE(?, photo_path), receipt_path = COALESCE(?, receipt_path), 
                        aadhaar = COALESCE(?, aadhaar), status = 'approved',
                        photo_status = IF(? != '' OR photo_path IS NOT NULL, 'verified', photo_status) 
                        WHERE id = ?");
                    
                    $genderFormatted = strtoupper($app['gender']);
                    if (!in_array($genderFormatted, ['MALE', 'FEMALE', 'OTHER'])) {
                        $genderFormatted = 'MALE';
                    }

                    $upAthlete->execute([
                        $app['full_name'], $genderFormatted, $app['dob'], $app['phone'], $app['email'],
                        $app['state'], $app['district'], $app['classification'], $app['wheelchair_status'],
                        $app['photo_path'], $app['receipt_path'], $app['aadhaar'], $app['photo_path'], $existingId
                    ]);

                    $upApp = $pdo->prepare("UPDATE athlete_applications SET status = 'approved', existing_athlete_id = ? WHERE id = ?");
                    $upApp->execute([$existingId, $applicationId]);

                    $hist = $pdo->prepare("INSERT INTO athlete_status_history (athlete_id, old_status, new_status, changed_by, remarks) VALUES (?, ?, 'approved', ?, ?)");
                    $hist->execute([$existingId, $existing['status'], $_SESSION['user_id'], "Linked and approved from application ID: $applicationId"]);

                    logAction($pdo, "Linked & Approved Athlete Application", "athletes", $existingId, "Name: {$app['full_name']} | REGN_NO: {$existing['regn_no']}");
                    $message = "<div class='alert alert-success border-0 p-3 mb-4 rounded-3' style='background-color:#ECFDF5; color:#065F46;'>Application linked to existing Athlete <strong>" . htmlspecialchars($existing['regn_no']) . "</strong> and approved successfully.</div>";
                } elseif ($action === 'approve_new') {
                    $pdo->beginTransaction();

                    $seqStmt = $pdo->query("SELECT athlete_last_no FROM registration_sequences FOR UPDATE");
                    $lastNo = (int)$seqStmt->fetchColumn();
                    
                    // Sync check: ensure lastNo is at least the MAX(CAST(regn_no AS UNSIGNED)) in athletes table
                    $maxAthStmt = $pdo->query("SELECT MAX(CAST(regn_no AS UNSIGNED)) FROM athletes");
                    $maxAthNo = (int)$maxAthStmt->fetchColumn();
                    if ($maxAthNo > $lastNo) {
                        $lastNo = $maxAthNo;
                    }
                    
                    $nextNo = $lastNo + 1;

                    $upSeq = $pdo->prepare("UPDATE registration_sequences SET athlete_last_no = ?");
                    $upSeq->execute([$nextNo]);

                    $regnNo = str_pad($nextNo, 4, '0', STR_PAD_LEFT);

                    $stateStmt = $pdo->prepare("SELECT id FROM states WHERE name = ?");
                    $stateStmt->execute([$app['state']]);
                    $stateRow = $stateStmt->fetch();
                    $stateId = $stateRow ? $stateRow['id'] : null;

                    $assocId = null;
                    if ($stateId) {
                        $assocStmt = $pdo->prepare("SELECT id FROM state_associations WHERE state_id = ? LIMIT 1");
                        $assocStmt->execute([$stateId]);
                        $assocId = $assocStmt->fetchColumn();
                    }

                    $genderFormatted = strtoupper($app['gender']);
                    if (!in_array($genderFormatted, ['MALE', 'FEMALE', 'OTHER'])) {
                        $genderFormatted = 'MALE';
                    }

                    // Newly approved athlete sets photo_status to verified if photo exists
                    $insAthlete = $pdo->prepare("INSERT INTO athletes 
                        (regn_no, full_name, gender, dob, mobile, email, state, district, classification, representing_for, state_association_id, wheelchair_status, photo_path, receipt_path, status, aadhaar, digilocker_imported, photo_status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved', ?, 0, ?)");
                    
                    $hasPhoto = !empty($app['photo_path']) ? 'verified' : 'missing';
                    $insAthlete->execute([
                        $regnNo, $app['full_name'], $genderFormatted, $app['dob'], $app['phone'], $app['email'],
                        $app['state'], $app['district'], $app['classification'], $app['state'], $assocId,
                        $app['wheelchair_status'], $app['photo_path'], $app['receipt_path'], $app['aadhaar'], $hasPhoto
                    ]);
                    $newAthleteId = $pdo->lastInsertId();

                    $upApp = $pdo->prepare("UPDATE athlete_applications SET status = 'approved', existing_athlete_id = ? WHERE id = ?");
                    $upApp->execute([$newAthleteId, $applicationId]);

                    $hist = $pdo->prepare("INSERT INTO athlete_status_history (athlete_id, old_status, new_status, changed_by, remarks) VALUES (?, NULL, 'approved', ?, ?)");
                    $hist->execute([$newAthleteId, $_SESSION['user_id'], "Newly approved athlete from application ID: $applicationId"]);

                    $pdo->commit();

                    logAction($pdo, "Approved New Athlete Application", "athletes", $newAthleteId, "Name: {$app['full_name']} | Generated REGN_NO: $regnNo");
                    $message = "<div class='alert alert-success border-0 p-3 mb-4 rounded-3' style='background-color:#ECFDF5; color:#065F46;'>Application approved successfully! Athlete REGN_NO: <strong>$regnNo</strong></div>";
                }
            } elseif ($type === 'official') {
                // Fetch application details
                $appStmt = $pdo->prepare("SELECT * FROM official_applications WHERE id = ?");
                $appStmt->execute([$applicationId]);
                $app = $appStmt->fetch(PDO::FETCH_ASSOC);

                if (!$app) {
                    throw new Exception("Official application not found.");
                }

                if ($action === 'reject') {
                    $up = $pdo->prepare("UPDATE official_applications SET status = 'rejected' WHERE id = ?");
                    $up->execute([$applicationId]);
                    logAction($pdo, "Rejected Official Application", "official_applications", $applicationId, "Name: {$app['full_name']}");
                    $message = "<div class='alert alert-success border-0 p-3 mb-4 rounded-3' style='background-color:#ECFDF5; color:#065F46;'>Official application for " . htmlspecialchars($app['full_name']) . " rejected successfully.</div>";
                } elseif ($action === 'approve_link') {
                    $existingId = (int)$_POST['existing_id'];
                    $existStmt = $pdo->prepare("SELECT * FROM officials WHERE id = ?");
                    $existStmt->execute([$existingId]);
                    $existing = $existStmt->fetch(PDO::FETCH_ASSOC);

                    if (!$existing) {
                        throw new Exception("Existing official profile not found for linking.");
                    }

                    // Update existing official details
                    $upOfficial = $pdo->prepare("UPDATE officials SET 
                        name = ?, role = ?, gender = ?, dob = ?, father_name = ?, 
                        state = ?, aadhaar = COALESCE(?, aadhaar), phone = ?, email = ?, 
                        address = ?, pincode = ?, kit_tshirt = ?, kit_tracksuit = ?, kit_shoe = ?, 
                        photo_path = COALESCE(?, photo_path), receipt_path = COALESCE(?, receipt_path), status = 'approved',
                        photo_status = IF(? != '' OR photo_path IS NOT NULL, 'verified', photo_status) 
                        WHERE id = ?");
                    
                    $upOfficial->execute([
                        $app['full_name'], $app['role'], $app['gender'], $app['dob'], $app['father_name'],
                        $app['state'], $app['aadhaar'], $app['phone'], $app['email'],
                        $app['address'], $app['pincode'], $app['kit_tshirt'], $app['kit_tracksuit'], $app['kit_shoe'],
                        $app['photo_path'], $app['receipt_path'], $app['photo_path'], $existingId
                    ]);

                    $upApp = $pdo->prepare("UPDATE official_applications SET status = 'approved', existing_official_id = ? WHERE id = ?");
                    $upApp->execute([$existingId, $applicationId]);

                    logAction($pdo, "Linked & Approved Official Application", "officials", $existingId, "Name: {$app['full_name']} | Official ID: {$existing['official_reg_no']}");
                    $message = "<div class='alert alert-success border-0 p-3 mb-4 rounded-3' style='background-color:#ECFDF5; color:#065F46;'>Application linked to existing Official <strong>" . htmlspecialchars($existing['official_reg_no']) . "</strong> and approved successfully.</div>";
                } elseif ($action === 'approve_new') {
                    $pdo->beginTransaction();

                    $seqStmt = $pdo->query("SELECT official_last_no FROM registration_sequences FOR UPDATE");
                    $lastNo = (int)$seqStmt->fetchColumn();
                    
                    // Sync check: ensure lastNo is at least the MAX of numerical portion of official_reg_no in officials table
                    $maxOffStmt = $pdo->query("SELECT MAX(CAST(SUBSTRING(official_reg_no, 4) AS UNSIGNED)) FROM officials WHERE official_reg_no LIKE 'OF-%'");
                    $maxOffNo = (int)$maxOffStmt->fetchColumn();
                    if ($maxOffNo > $lastNo) {
                        $lastNo = $maxOffNo;
                    }
                    
                    $nextNo = $lastNo + 1;

                    $upSeq = $pdo->prepare("UPDATE registration_sequences SET official_last_no = ?");
                    $upSeq->execute([$nextNo]);

                    $officialId = "OF-" . str_pad($nextNo, 4, '0', STR_PAD_LEFT);

                    $insOfficial = $pdo->prepare("INSERT INTO officials 
                        (official_reg_no, name, role, gender, dob, father_name, state, aadhaar, phone, email, address, pincode, kit_tshirt, kit_tracksuit, kit_shoe, photo_path, receipt_path, status, photo_status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved', ?)");
                    
                    $hasPhoto = !empty($app['photo_path']) ? 'verified' : 'missing';
                    $insOfficial->execute([
                        $officialId, $app['full_name'], $app['role'], $app['gender'], $app['dob'], $app['father_name'],
                        $app['state'], $app['aadhaar'], $app['phone'], $app['email'], $app['address'], $app['pincode'],
                        $app['kit_tshirt'], $app['kit_tracksuit'], $app['kit_shoe'], $app['photo_path'], $app['receipt_path'], $hasPhoto
                    ]);
                    $newOfficialId = $pdo->lastInsertId();

                    $upApp = $pdo->prepare("UPDATE official_applications SET status = 'approved', existing_official_id = ? WHERE id = ?");
                    $upApp->execute([$newOfficialId, $applicationId]);

                    $pdo->commit();

                    logAction($pdo, "Approved New Official Application", "officials", $newOfficialId, "Name: {$app['full_name']} | Generated ID: $officialId");
                    $message = "<div class='alert alert-success border-0 p-3 mb-4 rounded-3' style='background-color:#ECFDF5; color:#065F46;'>Application approved successfully! Official ID: <strong>$officialId</strong></div>";
                }
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $message = "<div class='alert alert-danger border-0 p-3 mb-4 rounded-3' style='background-color:#FEF2F2; color:#991B1B;'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

// Fetch pending queues
$athletesQueue = $pdo->query("SELECT * FROM athlete_applications WHERE status = 'pending' ORDER BY created_at ASC")->fetchAll(PDO::FETCH_ASSOC);
$officialsQueue = $pdo->query("SELECT * FROM official_applications WHERE status = 'pending' ORDER BY created_at ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch profile updates queue
$profileUpdatesQueue = $pdo->query("SELECT p.*, 
    IF(p.member_type = 'athlete', a.full_name, o.name) AS member_name,
    IF(p.member_type = 'athlete', a.regn_no, o.official_reg_no) AS member_reg_no,
    IF(p.member_type = 'athlete', a.email, o.email) AS current_email,
    IF(p.member_type = 'athlete', a.mobile, o.phone) AS current_phone,
    IF(p.member_type = 'athlete', a.photo_path, o.photo_path) AS current_photo_path
    FROM profile_update_requests p
    LEFT JOIN athletes a ON p.member_type = 'athlete' AND p.member_id = a.id
    LEFT JOIN officials o ON p.member_type = 'official' AND p.member_id = o.id
    WHERE p.status = 'pending'
    ORDER BY p.submitted_at ASC")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<div class="admin-wrapper">
    <div class="container-fluid" style="padding: 2rem;">
        
        <div class="admin-page-title-row">
            <div>
                <span class="admin-section-eyebrow">Review Queue Panel</span>
                <h1 class="admin-page-title">Pending Applications Review</h1>
            </div>
            <a href="dashboard.php" class="admin-btn admin-btn-outline">Return to Dashboard</a>
        </div>

        <?php echo $message; ?>

        <!-- Tabs Navigation -->
        <div style="display:flex; gap:1rem; margin-bottom:2rem; border-bottom:2px solid #E2E8F0; padding-bottom:1px;">
            <a href="?tab=athletes" style="text-decoration:none; padding:1rem 2rem; font-family:'Outfit',sans-serif; font-weight:700; font-size:1.1rem; border-bottom:3px solid <?php echo $tab === 'athletes' ? 'var(--bsfi-green)' : 'transparent'; ?>; color:<?php echo $tab === 'athletes' ? 'var(--bsfi-green)' : 'var(--text-secondary)'; ?>; transition:all 0.3s ease;">
                Athletes Queue (<?php echo count($athletesQueue); ?>)
            </a>
            <a href="?tab=officials" style="text-decoration:none; padding:1rem 2rem; font-family:'Outfit',sans-serif; font-weight:700; font-size:1.1rem; border-bottom:3px solid <?php echo $tab === 'officials' ? 'var(--bsfi-green)' : 'transparent'; ?>; color:<?php echo $tab === 'officials' ? 'var(--bsfi-green)' : 'var(--text-secondary)'; ?>; transition:all 0.3s ease;">
                Officials Queue (<?php echo count($officialsQueue); ?>)
            </a>
            <a href="?tab=profile_updates" style="text-decoration:none; padding:1rem 2rem; font-family:'Outfit',sans-serif; font-weight:700; font-size:1.1rem; border-bottom:3px solid <?php echo $tab === 'profile_updates' ? 'var(--bsfi-green)' : 'transparent'; ?>; color:<?php echo $tab === 'profile_updates' ? 'var(--bsfi-green)' : 'var(--text-secondary)'; ?>; transition:all 0.3s ease;">
                Profile Updates (<?php echo count($profileUpdatesQueue); ?>)
            </a>
        </div>

        <!-- Queues Panel Content -->
        <div style="display:flex; flex-direction:column; gap:2rem;">
            <?php if ($tab === 'athletes'): ?>
                <?php if (count($athletesQueue) > 0): ?>
                    <?php foreach ($athletesQueue as $app): ?>
                        <div class="admin-card">
                            
                            <!-- Header Info -->
                            <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:2rem; border-bottom:1px solid #E2E8F0; padding-bottom:1.5rem;">
                                <div>
                                    <h3 class="admin-card-title" style="font-size:1.6rem; margin:0;"><?php echo htmlspecialchars($app['full_name']); ?></h3>
                                    <span style="color:var(--text-muted); font-size:0.85rem;">Submitted on: <?php echo date('d M Y, h:i A', strtotime($app['created_at'])); ?></span>
                                </div>
                                <?php if ($app['possible_duplicate']): ?>
                                    <span class="admin-badge admin-badge-danger" style="padding:0.4rem 1rem; font-size:0.85rem;">
                                        Potential Duplicate (Score: <?php echo $app['duplicate_score']; ?>)
                                    </span>
                                <?php else: ?>
                                    <span class="admin-badge admin-badge-success" style="padding:0.4rem 1rem; font-size:0.85rem;">
                                        New Registration Profile
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Side by Side Layout for Duplicate Check -->
                            <?php if ($app['possible_duplicate'] && $app['existing_athlete_id']): 
                                // Fetch the matching existing athlete records
                                $exStmt = $pdo->prepare("SELECT * FROM athletes WHERE id = ?");
                                $exStmt->execute([$app['existing_athlete_id']]);
                                $ex = $exStmt->fetch(PDO::FETCH_ASSOC);
                            ?>
                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem; margin-bottom:2rem;">
                                    
                                    <!-- Left Side: Application Details -->
                                    <div style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:12px; padding:1.5rem;">
                                        <h4 style="color:var(--bsfi-saffron); border-bottom:1px solid #E2E8F0; padding-bottom:0.5rem; margin-top:0; font-weight:700;">Submitted Application</h4>
                                        <div style="display:grid; grid-template-columns:1fr 1.5fr; gap:0.5rem; font-size:0.88rem; color:var(--text-primary);">
                                            <div><strong>Name:</strong></div><div><?php echo htmlspecialchars($app['full_name']); ?></div>
                                            <div><strong>DOB:</strong></div><div><?php echo htmlspecialchars($app['dob']); ?></div>
                                            <div><strong>Email:</strong></div><div><?php echo htmlspecialchars($app['email']); ?></div>
                                            <div><strong>Phone:</strong></div><div><?php echo htmlspecialchars($app['phone']); ?></div>
                                            <div><strong>Aadhaar:</strong></div><div><?php echo htmlspecialchars($app['aadhaar'] ?: 'N/A'); ?></div>
                                            <div><strong>State:</strong></div><div><?php echo htmlspecialchars($app['state']); ?></div>
                                            <div><strong>Classification:</strong></div><div><span class="admin-badge admin-badge-info"><?php echo htmlspecialchars($app['classification']); ?></span></div>
                                            <div><strong>Wheelchair:</strong></div><div><?php echo htmlspecialchars($app['wheelchair_status']); ?></div>
                                        </div>
                                    </div>

                                    <!-- Right Side: Existing Record Details -->
                                    <?php if ($ex): ?>
                                        <div style="background:rgba(255,153,51,0.03); border:1px solid rgba(255,153,51,0.2); border-radius:12px; padding:1.5rem;">
                                            <h4 style="color:var(--navy); border-bottom:1px solid #E2E8F0; padding-bottom:0.5rem; margin-top:0; font-weight:700;">Existing Athlete (REGN_NO: <?php echo htmlspecialchars($ex['regn_no']); ?>)</h4>
                                            <div style="display:grid; grid-template-columns:1fr 1.5fr; gap:0.5rem; font-size:0.88rem; color:var(--text-primary);">
                                                <div><strong>Name:</strong></div><div><?php echo htmlspecialchars($ex['full_name']); ?></div>
                                                <div><strong>DOB:</strong></div><div><?php echo htmlspecialchars($ex['dob']); ?></div>
                                                <div><strong>Email:</strong></div><div><?php echo htmlspecialchars($ex['email']); ?></div>
                                                <div><strong>Phone:</strong></div><div><?php echo htmlspecialchars($ex['mobile']); ?></div>
                                                <div><strong>Aadhaar:</strong></div><div><?php echo htmlspecialchars($ex['aadhaar'] ?: 'N/A'); ?></div>
                                                <div><strong>State:</strong></div><div><?php echo htmlspecialchars($ex['state']); ?></div>
                                                <div><strong>Classification:</strong></div><div><span class="admin-badge admin-badge-info"><?php echo htmlspecialchars($ex['classification']); ?></span></div>
                                                <div><strong>Wheelchair:</strong></div><div><?php echo htmlspecialchars($ex['wheelchair_status']); ?></div>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                </div>
                            <?php else: ?>
                                <!-- Standard fields details display if no duplicates -->
                                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1.5rem; margin-bottom:2rem; font-size:0.9rem; color:var(--text-secondary);">
                                    <div><strong>Gender:</strong> <?php echo htmlspecialchars($app['gender']); ?></div>
                                    <div><strong>Date of Birth:</strong> <?php echo htmlspecialchars($app['dob']); ?></div>
                                    <div><strong>Phone:</strong> <?php echo htmlspecialchars($app['phone']); ?></div>
                                    <div><strong>Email:</strong> <?php echo htmlspecialchars($app['email']); ?></div>
                                    <div><strong>State:</strong> <?php echo htmlspecialchars($app['state']); ?></div>
                                    <div><strong>District:</strong> <?php echo htmlspecialchars($app['district']); ?></div>
                                    <div><strong>Classification:</strong> <?php echo htmlspecialchars($app['classification']); ?></div>
                                    <div><strong>Aadhaar No:</strong> <?php echo htmlspecialchars($app['aadhaar'] ?: 'N/A'); ?></div>
                                </div>
                            <?php endif; ?>

                            <!-- Actions Row -->
                            <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid #E2E8F0; padding-top:1.5rem;">
                                <div style="display:flex; gap:1rem;">
                                    <?php if (!empty($app['photo_path'])): ?>
                                        <a href="../<?php echo htmlspecialchars($app['photo_path']); ?>" target="_blank" class="admin-btn admin-btn-outline">View Photo</a>
                                    <?php endif; ?>
                                    <?php if (!empty($app['receipt_path'])): ?>
                                        <a href="../<?php echo htmlspecialchars($app['receipt_path']); ?>" target="_blank" class="admin-btn admin-btn-outline">View ID Proof</a>
                                    <?php endif; ?>
                                </div>
                                <form action="registrations.php?tab=athletes" method="POST" style="display:flex; gap:0.5rem; margin:0;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="type" value="athlete">
                                    <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                    
                                    <?php if ($app['possible_duplicate'] && $app['existing_athlete_id']): ?>
                                        <input type="hidden" name="existing_id" value="<?php echo $app['existing_athlete_id']; ?>">
                                        <button type="submit" name="action" value="approve_link" class="admin-btn admin-btn-warning">Approve &amp; Link Profile</button>
                                        <button type="submit" name="action" value="approve_new" class="admin-btn admin-btn-primary">Approve as New Athlete</button>
                                    <?php else: ?>
                                        <button type="submit" name="action" value="approve_new" class="admin-btn admin-btn-primary">Approve Registration</button>
                                    <?php endif; ?>
                                    <button type="submit" name="action" value="reject" class="admin-btn admin-btn-danger">Reject</button>
                                </form>
                            </div>

                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="admin-card" style="text-align:center; padding: 4rem;">
                        <p style="font-size:1.15rem; color:var(--text-secondary); margin:0;">All clear! There are no pending athlete registrations to review.</p>
                    </div>
                <?php endif; ?>
            <?php elseif ($tab === 'officials'): ?>
                <?php if (count($officialsQueue) > 0): ?>
                    <?php foreach ($officialsQueue as $app): ?>
                        <div class="admin-card">
                            
                            <!-- Header Info -->
                            <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:2rem; border-bottom:1px solid #E2E8F0; padding-bottom:1.5rem;">
                                <div>
                                    <h3 class="admin-card-title" style="font-size:1.6rem; margin:0;"><?php echo htmlspecialchars($app['full_name']); ?></h3>
                                    <span style="color:var(--text-muted); font-size:0.85rem;">Submitted on: <?php echo date('d M Y, h:i A', strtotime($app['created_at'])); ?></span>
                                </div>
                                <?php if ($app['possible_duplicate']): ?>
                                    <span class="admin-badge admin-badge-danger" style="padding:0.4rem 1rem; font-size:0.85rem;">
                                        Potential Duplicate (Score: <?php echo $app['duplicate_score']; ?>)
                                    </span>
                                <?php else: ?>
                                    <span class="admin-badge admin-badge-success" style="padding:0.4rem 1rem; font-size:0.85rem;">
                                        New Registration Profile
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Side by Side Layout for Duplicate Check -->
                            <?php if ($app['possible_duplicate'] && $app['existing_official_id']): 
                                // Fetch the matching existing official records
                                $exStmt = $pdo->prepare("SELECT * FROM officials WHERE id = ?");
                                $exStmt->execute([$app['existing_official_id']]);
                                $ex = $exStmt->fetch(PDO::FETCH_ASSOC);
                            ?>
                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem; margin-bottom:2rem;">
                                    
                                    <!-- Left Side: Application Details -->
                                    <div style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:12px; padding:1.5rem;">
                                        <h4 style="color:var(--bsfi-saffron); border-bottom:1px solid #E2E8F0; padding-bottom:0.5rem; margin-top:0; font-weight:700;">Submitted Application</h4>
                                        <div style="display:grid; grid-template-columns:1fr 1.5fr; gap:0.5rem; font-size:0.88rem; color:var(--text-primary);">
                                            <div><strong>Name:</strong></div><div><?php echo htmlspecialchars($app['full_name']); ?></div>
                                            <div><strong>Role:</strong></div><div><span class="admin-badge admin-badge-info"><?php echo htmlspecialchars($app['role']); ?></span></div>
                                            <div><strong>DOB:</strong></div><div><?php echo htmlspecialchars($app['dob']); ?></div>
                                            <div><strong>Email:</strong></div><div><?php echo htmlspecialchars($app['email']); ?></div>
                                            <div><strong>Phone:</strong></div><div><?php echo htmlspecialchars($app['phone']); ?></div>
                                            <div><strong>Aadhaar:</strong></div><div><?php echo htmlspecialchars($app['aadhaar'] ?: 'N/A'); ?></div>
                                            <div><strong>State:</strong></div><div><?php echo htmlspecialchars($app['state']); ?></div>
                                        </div>
                                    </div>

                                    <!-- Right Side: Existing Record Details -->
                                    <?php if ($ex): ?>
                                        <div style="background:rgba(255,153,51,0.03); border:1px solid rgba(255,153,51,0.2); border-radius:12px; padding:1.5rem;">
                                            <h4 style="color:var(--navy); border-bottom:1px solid #E2E8F0; padding-bottom:0.5rem; margin-top:0; font-weight:700;">Existing Official (ID: <?php echo htmlspecialchars($ex['official_reg_no']); ?>)</h4>
                                            <div style="display:grid; grid-template-columns:1fr 1.5fr; gap:0.5rem; font-size:0.88rem; color:var(--text-primary);">
                                                <div><strong>Name:</strong></div><div><?php echo htmlspecialchars($ex['name']); ?></div>
                                                <div><strong>Role:</strong></div><div><span class="admin-badge admin-badge-info"><?php echo htmlspecialchars($ex['role']); ?></span></div>
                                                <div><strong>DOB:</strong></div><div><?php echo htmlspecialchars($ex['dob']); ?></div>
                                                <div><strong>Email:</strong></div><div><?php echo htmlspecialchars($ex['email']); ?></div>
                                                <div><strong>Phone:</strong></div><div><?php echo htmlspecialchars($ex['phone']); ?></div>
                                                <div><strong>Aadhaar:</strong></div><div><?php echo htmlspecialchars($ex['aadhaar'] ?: 'N/A'); ?></div>
                                                <div><strong>State:</strong></div><div><?php echo htmlspecialchars($ex['state']); ?></div>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                </div>
                            <?php else: ?>
                                <!-- Standard fields details display if no duplicates -->
                                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1.5rem; margin-bottom:2rem; font-size:0.9rem; color:var(--text-secondary);">
                                    <div><strong>Role:</strong> <?php echo htmlspecialchars($app['role']); ?></div>
                                    <div><strong>Gender:</strong> <?php echo htmlspecialchars($app['gender']); ?></div>
                                    <div><strong>Date of Birth:</strong> <?php echo htmlspecialchars($app['dob']); ?></div>
                                    <div><strong>Phone:</strong> <?php echo htmlspecialchars($app['phone']); ?></div>
                                    <div><strong>Email:</strong> <?php echo htmlspecialchars($app['email']); ?></div>
                                    <div><strong>State:</strong> <?php echo htmlspecialchars($app['state']); ?></div>
                                    <div><strong>Aadhaar No:</strong> <?php echo htmlspecialchars($app['aadhaar'] ?: 'N/A'); ?></div>
                                </div>
                            <?php endif; ?>

                            <!-- Actions Row -->
                            <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid #E2E8F0; padding-top:1.5rem;">
                                <div style="display:flex; gap:1rem;">
                                    <?php if (!empty($app['photo_path'])): ?>
                                        <a href="../<?php echo htmlspecialchars($app['photo_path']); ?>" target="_blank" class="admin-btn admin-btn-outline">View Photo</a>
                                    <?php endif; ?>
                                    <?php if (!empty($app['receipt_path'])): ?>
                                        <a href="../<?php echo htmlspecialchars($app['receipt_path']); ?>" target="_blank" class="admin-btn admin-btn-outline">View ID Proof</a>
                                    <?php endif; ?>
                                </div>
                                <form action="registrations.php?tab=officials" method="POST" style="display:flex; gap:0.5rem; margin:0;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="type" value="official">
                                    <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                    
                                    <?php if ($app['possible_duplicate'] && $app['existing_official_id']): ?>
                                        <input type="hidden" name="existing_id" value="<?php echo $app['existing_official_id']; ?>">
                                        <button type="submit" name="action" value="approve_link" class="admin-btn admin-btn-warning">Approve &amp; Link Profile</button>
                                        <button type="submit" name="action" value="approve_new" class="admin-btn admin-btn-primary">Approve as New Official</button>
                                    <?php else: ?>
                                        <button type="submit" name="action" value="approve_new" class="admin-btn admin-btn-primary">Approve Registration</button>
                                    <?php endif; ?>
                                    <button type="submit" name="action" value="reject" class="admin-btn admin-btn-danger">Reject</button>
                                </form>
                            </div>

                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="admin-card" style="text-align:center; padding: 4rem;">
                        <p style="font-size:1.15rem; color:var(--text-secondary); margin:0;">All clear! There are no pending official registrations to review.</p>
                    </div>
                <?php endif; ?>
            <?php elseif ($tab === 'profile_updates'): ?>
                <?php if (count($profileUpdatesQueue) > 0): ?>
                    <?php foreach ($profileUpdatesQueue as $req): ?>
                        <div class="admin-card" style="margin-bottom: 2rem;">
                            
                            <!-- Header Info -->
                            <div style="display:flex; justify-content:space-between; align-items:start; margin-bottom:2rem; border-bottom:1px solid #E2E8F0; padding-bottom:1.5rem;">
                                <div>
                                    <h3 class="admin-card-title" style="font-size:1.6rem; margin:0;">
                                        Profile Update Request: <?php echo htmlspecialchars($req['member_name']); ?> 
                                        <span style="font-size:1rem; color:var(--text-muted);">(<?php echo htmlspecialchars($req['member_reg_no']); ?>)</span>
                                    </h3>
                                    <span style="color:var(--text-muted); font-size:0.85rem;">Member Type: <strong style="text-transform:uppercase; color:var(--bsfi-saffron);"><?php echo $req['member_type']; ?></strong> | Submitted: <?php echo date('d M Y, h:i A', strtotime($req['submitted_at'])); ?></span>
                                </div>
                            </div>

                            <!-- Comparison Grid -->
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem; margin-bottom:2.5rem;">
                                
                                <!-- Left Side: Current Profile -->
                                <div style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:12px; padding:1.5rem; text-align:left;">
                                    <h4 style="color:var(--text-primary); border-bottom:1px solid #E2E8F0; padding-bottom:0.5rem; margin-top:0; font-weight:700;">Current Live Profile</h4>
                                    
                                    <div style="display:flex; align-items:center; gap:1.5rem; margin-bottom:1.5rem;">
                                        <?php if (!empty($req['current_photo_path'])): ?>
                                            <img src="../<?php echo htmlspecialchars($req['current_photo_path']); ?>" alt="Current Photo" style="width: 80px; height: 80px; object-fit: cover; border-radius: 50%; border: 2px solid #E2E8F0;">
                                        <?php else: ?>
                                            <div style="width: 80px; height: 80px; border-radius: 50%; background:#CBD5E1; display:flex; align-items:center; justify-content:center; border:2px dashed #94A3B8;">
                                                <span style="font-size: 0.72rem; font-weight: 700; color:#FFFFFF; text-transform: uppercase;">No Pic</span>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <span style="font-size:0.8rem; color:var(--text-muted); text-transform:uppercase; display:block;">Live Photograph</span>
                                            <span style="font-size:0.9rem; font-weight:600; color:var(--text-primary);"><?php echo !empty($req['current_photo_path']) ? 'Verified Picture' : 'No Photo (Silhouette Placed)'; ?></span>
                                        </div>
                                    </div>

                                    <div style="display:grid; grid-template-columns:1fr 2fr; gap:0.5rem; font-size:0.88rem; color:var(--text-secondary);">
                                        <div><strong>Email:</strong></div><div><?php echo htmlspecialchars($req['current_email'] ?: 'N/A'); ?></div>
                                        <div><strong>Phone/Mobile:</strong></div><div><?php echo htmlspecialchars($req['current_phone'] ?: 'N/A'); ?></div>
                                    </div>
                                </div>

                                <!-- Right Side: Requested Profile Updates -->
                                <div style="background:rgba(19, 136, 8, 0.03); border:1px solid rgba(19, 136, 8, 0.2); border-radius:12px; padding:1.5rem; text-align:left;">
                                    <h4 style="color:var(--bsfi-green); border-bottom:1px solid rgba(19, 136, 8, 0.1); padding-bottom:0.5rem; margin-top:0; font-weight:700;">Requested Updates</h4>

                                    <div style="display:flex; align-items:center; gap:1.5rem; margin-bottom:1.5rem;">
                                        <?php if (!empty($req['requested_photo_path'])): ?>
                                            <img src="../<?php echo htmlspecialchars($req['requested_photo_path']); ?>" alt="Requested Photo" style="width: 80px; height: 80px; object-fit: cover; border-radius: 50%; border: 2px solid var(--bsfi-green);">
                                            <div>
                                                <span style="font-size:0.8rem; color:var(--bsfi-green); text-transform:uppercase; display:block; font-weight:600;">New Uploaded Photograph</span>
                                                <a href="../<?php echo htmlspecialchars($req['requested_photo_path']); ?>" target="_blank" style="font-size:0.85rem; color:var(--navy); text-decoration:underline;">View Full Resolution</a>
                                            </div>
                                        <?php else: ?>
                                            <div style="width: 80px; height: 80px; border-radius: 50%; background:#E2E8F0; display:flex; align-items:center; justify-content:center; border:2px dashed #CBD5E1;">
                                                <span style="font-size: 0.8rem; color:#94A3B8; font-weight: bold; text-align: center; text-transform: uppercase;">No Photo</span>
                                            </div>
                                            <div>
                                                <span style="font-size:0.8rem; color:var(--text-muted); text-transform:uppercase; display:block;">New Photograph</span>
                                                <span style="font-size:0.9rem; color:var(--text-muted); font-style:italic;">No photo change requested</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div style="display:grid; grid-template-columns:1fr 2fr; gap:0.5rem; font-size:0.88rem; color:var(--text-primary);">
                                        <div><strong>Requested Email:</strong></div><div style="<?php echo $req['requested_email'] !== $req['current_email'] ? 'color:var(--bsfi-green); font-weight:700;' : ''; ?>"><?php echo htmlspecialchars($req['requested_email'] ?: 'N/A'); ?></div>
                                        <div><strong>Requested Phone:</strong></div><div style="<?php echo $req['requested_phone'] !== $req['current_phone'] ? 'color:var(--bsfi-green); font-weight:700;' : ''; ?>"><?php echo htmlspecialchars($req['requested_phone'] ?: 'N/A'); ?></div>
                                        <div><strong>Requested Address:</strong></div><div><?php echo htmlspecialchars($req['requested_address'] ?: 'N/A'); ?></div>
                                        <div><strong>Requested Pincode:</strong></div><div><?php echo htmlspecialchars($req['requested_pincode'] ?: 'N/A'); ?></div>
                                    </div>
                                </div>

                            </div>

                            <!-- Review Decision Form -->
                            <form action="registrations.php?tab=profile_updates" method="POST" style="border-top:1px solid #E2E8F0; padding-top:1.5rem; display:flex; flex-direction:column; gap:1rem; margin:0;">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                
                                <div class="admin-form-group" style="text-align:left;">
                                    <label>Administrator Decision Notes (Reason for approval or rejection reasons like: blurry photo, wrong type)</label>
                                    <input type="text" name="review_notes" class="admin-input" placeholder="E.g. Approved. Profile picture verified. / Rejected due to poor lighting.">
                                </div>

                                <div style="display:flex; justify-content:flex-end; gap:0.5rem; margin-top:0.5rem;">
                                    <button type="submit" name="action" value="approve_update" class="admin-btn admin-btn-primary">Approve &amp; Update Live Profile</button>
                                    <button type="submit" name="action" value="reject_update" class="admin-btn admin-btn-danger">Reject Update</button>
                                </div>
                            </form>

                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="admin-card" style="text-align:center; padding: 4rem;">
                        <p style="font-size:1.15rem; color:var(--text-secondary); margin:0;">All clear! There are no pending profile update requests to review.</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
