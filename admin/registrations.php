<?php
// registrations.php - Admin / Editor registrations verification panel
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role-check.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/mailer.php';

// Restricted to admin & editor for processing actions
requireLogin();
if (!in_array($_SESSION['role'], ['admin', 'editor'])) {
    checkRole(['admin', 'editor']); // triggers access denied screen
}

function sendApprovalEmail($email, $name, $regNo, $type) {
    if (empty($email)) return;
    $subject  = $type === 'athlete' ? 'BSFI Athlete Registration Approved' : 'BSFI Official Registration Approved';
    $roleName = $type === 'athlete' ? 'Athlete / Player' : 'Official / Coach / Referee';
    $html = "
      <div style='font-family: sans-serif; padding: 20px; max-width: 600px; border: 1px solid #e2e8f0; border-radius: 10px;'>
        <h2 style='color: #081B4B; margin-bottom: 20px;'>Boccia Sports Federation of India</h2>
        <p>Dear {$name},</p>
        <p>We are pleased to inform you that your registration application as an <strong>{$roleName}</strong> has been approved by the BSFI federation administration.</p>
        <div style='background: #f1f5f9; padding: 15px; margin: 20px 0; border-radius: 6px; font-size: 16px;'>
          <strong>Registration Number:</strong> {$regNo}<br/>
          <strong>Name:</strong> {$name}<br/>
          <strong>Status:</strong> Approved
        </div>
        <p>You can now verify your active membership on our website at any time using your registration number.</p>
        <p style='margin-top: 30px;'>Best Regards,<br/>Boccia Sports Federation of India (BSFI)</p>
      </div>
    ";
    sendEmail($email, $subject, $html);
}

function sendRejectionEmail($email, $name, $refNo, $type, $reason) {
    if (empty($email)) return;
    $subject  = $type === 'athlete' ? 'BSFI Athlete Registration Application Update' : 'BSFI Official Registration Application Update';
    $roleName = $type === 'athlete' ? 'Athlete / Player' : 'Official / Coach / Referee';
    $reasonText = !empty($reason) ? htmlspecialchars($reason) : 'Documentation requirements or profile information criteria not met.';
    $html = "
      <div style='font-family: sans-serif; padding: 20px; max-width: 600px; border: 1px solid #e2e8f0; border-radius: 10px; background-color: #ffffff;'>
        <h2 style='color: #8C201C; margin-bottom: 20px; font-weight: 800;'>Boccia Sports Federation of India</h2>
        <p>Dear {$name},</p>
        <p>Thank you for submitting your registration application as an <strong>{$roleName}</strong> to the Boccia Sports Federation of India (BSFI).</p>
        <p>After reviewing your submitted application, the BSFI verification committee has <strong>not approved</strong> your registration at this time.</p>
        <div style='background-color: #fef2f2; border-left: 4px solid #dc2626; padding: 15px; margin: 20px 0; border-radius: 6px;'>
          <strong style='color: #991b1b; font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em;'>Reason for Rejection / Comments:</strong>
          <p style='color: #7f1d1d; margin: 8px 0 0 0; font-size: 15px; font-weight: 600;'>{$reasonText}</p>
        </div>
        <div style='background-color: #f8fafc; padding: 12px 15px; border-radius: 6px; font-size: 14px; color: #334155; margin-bottom: 20px;'>
          <strong>Reference ID:</strong> {$refNo}
        </div>
        <p>If you believe this is an error or wish to submit corrected documents, please visit our <a href='https://www.bocciaindia.com/get-involved/status.php?id={$refNo}&email=" . urlencode($email) . "' style='color: #081B4B; font-weight: bold;'>Status Tracking Portal</a> or contact the federation at <a href='mailto:office@bocciaindia.com' style='color: #081B4B;'>office@bocciaindia.com</a>.</p>
        <p style='margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 15px; font-size: 13px; color: #64748b;'>Best Regards,<br/><strong>Boccia Sports Federation of India (BSFI)</strong></p>
      </div>
    ";
    
    sendEmail($email, $subject, $html);

    global $pdo;
    try {
        $log = $pdo->prepare("INSERT INTO activity_logs (action, details) VALUES (?, ?)");
        $log->execute(['Email Rejection Sent', "Rejection email sent to: {$email} for Ref: {$refNo}"]);
    } catch (\Throwable $t) {}
}

function sendUpdateApprovalEmail($email, $name, $regNo, $type) {
    if (empty($email)) return;
    $roleName = $type === 'athlete' ? 'Athlete / Player' : 'Official / Coach / Referee';
    $html = "
      <div style='font-family: sans-serif; padding: 20px; max-width: 600px; border: 1px solid #e2e8f0; border-radius: 10px;'>
        <h2 style='color: #081B4B; margin-bottom: 20px;'>Boccia Sports Federation of India</h2>
        <p>Dear {$name},</p>
        <p>Your profile details update request for Registration Number <strong>{$regNo}</strong> ({$roleName}) has been approved and applied to your profile record.</p>
        <p>You can now check your updated profile on the membership verification page.</p>
        <p style='margin-top: 30px;'>Best Regards,<br/>Boccia Sports Federation of India (BSFI)</p>
      </div>
    ";
    sendEmail($email, 'BSFI Profile Update Approved', $html);

    // Get DB handle from global scope
    global $pdo;
    try {
        if ($httpCode >= 200 && $httpCode < 300) {
            $log = $pdo->prepare("INSERT INTO activity_logs (action, details) VALUES (?, ?)");
            $log->execute(['Email Update Approved Sent', "Update approved email sent to: {$email} for {$regNo}"]);
        } else {
            error_log("Resend API failed to send update approval to {$email}. Code: {$httpCode}, Response: {$res}");
            $log = $pdo->prepare("INSERT INTO activity_logs (action, details) VALUES (?, ?)");
            $log->execute(['Email Update Approved Failed', "HTTP Code: {$httpCode}, Response: {$res}"]);
        }
    } catch (\Throwable $t) {
        error_log("Failed to write email activity log: " . $t->getMessage());
    }
}

function sendUpdateRejectionEmail($email, $name, $regNo, $type, $notes) {
    if (empty($email)) return;
    $roleName = $type === 'athlete' ? 'Athlete / Player' : 'Official / Coach / Referee';
    $reasonText = !empty($notes) ? htmlspecialchars($notes) : 'Documentation requirements or profile information criteria not met.';
    $html = "
      <div style='font-family: sans-serif; padding: 20px; max-width: 600px; border: 1px solid #e2e8f0; border-radius: 10px; background-color: #ffffff;'>
        <h2 style='color: #8C201C; margin-bottom: 20px; font-weight: 800;'>Boccia Sports Federation of India</h2>
        <p>Dear {$name},</p>
        <p>Your profile update request for Registration Number <strong>{$regNo}</strong> ({$roleName}) has been reviewed by the BSFI federation administration.</p>
        <p>After reviewing your request, the administration has <strong>not approved</strong> the requested profile updates at this time.</p>
        <div style='background-color: #fef2f2; border-left: 4px solid #dc2626; padding: 15px; margin: 20px 0; border-radius: 6px;'>
          <strong style='color: #991b1b; font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em;'>Admin Decision Notes / Corrective Actions Needed:</strong>
          <p style='color: #7f1d1d; margin: 8px 0 0 0; font-size: 15px; font-weight: 600;'>{$reasonText}</p>
        </div>
        <p>You can re-submit your profile update request with the required corrections anytime via our <a href='https://www.bocciaindia.com/get-involved/update-profile.php?type={$type}&id={$regNo}' style='color: #081B4B; font-weight: bold;'>Profile Update Portal</a>.</p>
        <p style='margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 15px; font-size: 13px; color: #64748b;'>Best Regards,<br/><strong>Boccia Sports Federation of India (BSFI)</strong></p>
      </div>
    ";
    sendEmail($email, 'BSFI Profile Update Request Status', $html);
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
            if ($action === 'reopen') {
                $targetTable = ($type === 'official') ? 'official_applications' : 'athlete_applications';
                $up = $pdo->prepare("UPDATE {$targetTable} SET status = 'pending' WHERE id = ?");
                $up->execute([$applicationId]);
                logAction($pdo, "Reopened Application", $targetTable, $applicationId, "Type: $type | ID: $applicationId");
                $message = "<div class='alert alert-success border-0 p-3 mb-4 rounded-3' style='background-color:#ECFDF5; color:#065F46;'>Application reopened successfully and moved back to the active review queue.</div>";
            } elseif ($action === 'delete_application') {
                $targetTable = ($type === 'official') ? 'official_applications' : 'athlete_applications';
                $del = $pdo->prepare("DELETE FROM {$targetTable} WHERE id = ?");
                $del->execute([$applicationId]);
                logAction($pdo, "Deleted Application Record", $targetTable, $applicationId, "Type: $type | ID: $applicationId");
                $message = "<div class='alert alert-success border-0 p-3 mb-4 rounded-3' style='background-color:#ECFDF5; color:#065F46;'>Application record permanently deleted.</div>";
            } elseif ($action === 'approve_update' || $action === 'reject_update') {
                // Fetch request details
                $reqStmt = $pdo->prepare("SELECT * FROM profile_update_requests WHERE id = ?");
                $reqStmt->execute([$requestId]);
                $req = $reqStmt->fetch(PDO::FETCH_ASSOC);

                if (!$req) {
                    throw new Exception("Profile update request not found.");
                }

                if ($req['status'] !== 'pending') {
                    throw new Exception("This profile update request has already been processed (current status: " . $req['status'] . ").");
                }

                $notes = isset($_POST['review_notes']) ? trim($_POST['review_notes']) : '';
                $memberId = (int)$req['member_id'];
                $memberType = $req['member_type'];

                if ($action === 'reject_update') {
                    $up = $pdo->prepare("UPDATE profile_update_requests SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW(), review_notes = ? WHERE id = ?");
                    $up->execute([$_SESSION['user_id'], $notes, $requestId]);
                    
                    // Fetch member info to send rejection email
                    $regNo = '';
                    $name = '';
                    $notifyEmail = $req['requested_email'];
                    if ($memberType === 'athlete') {
                        $stmt = $pdo->prepare("SELECT regn_no, full_name, email FROM athletes WHERE id = ?");
                        $stmt->execute([$memberId]);
                        $mRow = $stmt->fetch();
                        if ($mRow) {
                            $regNo = $mRow['regn_no'];
                            $name = $mRow['full_name'];
                            if (empty($notifyEmail)) $notifyEmail = $mRow['email'];
                        }
                    } else {
                        $stmt = $pdo->prepare("SELECT official_reg_no, name, email FROM officials WHERE id = ?");
                        $stmt->execute([$memberId]);
                        $mRow = $stmt->fetch();
                        if ($mRow) {
                            $regNo = $mRow['official_reg_no'];
                            $name = $mRow['name'];
                            if (empty($notifyEmail)) $notifyEmail = $mRow['email'];
                        }
                    }

                    sendUpdateRejectionEmail($notifyEmail, $name, $regNo, $memberType, $notes);

                    logAction($pdo, "Rejected Profile Update Request", "profile_update_requests", $requestId, "Type: $memberType | ID: $memberId");
                    $message = "<div class='alert alert-success border-0 p-3 mb-4 rounded-3' style='background-color:#ECFDF5; color:#065F46;'>Profile update request rejected and rejection email sent to applicant.</div>";
                } elseif ($action === 'approve_update') {
                    $pdo->beginTransaction();

                    if ($memberType === 'athlete') {
                        // Update live athlete details
                        $upAthlete = $pdo->prepare("UPDATE athletes SET 
                            email = COALESCE(NULLIF(?, ''), email),
                            mobile = COALESCE(NULLIF(?, ''), mobile),
                            address = COALESCE(NULLIF(?, ''), address),
                            pincode = COALESCE(NULLIF(?, ''), pincode),
                            state = COALESCE(NULLIF(?, ''), state),
                            representing_for = COALESCE(NULLIF(?, ''), representing_for),
                            kit_tshirt = COALESCE(NULLIF(?, ''), kit_tshirt),
                            kit_tracksuit = COALESCE(NULLIF(?, ''), kit_tracksuit),
                            kit_shoe = COALESCE(NULLIF(?, ''), kit_shoe),
                            aadhaar = COALESCE(NULLIF(?, ''), aadhaar),
                            impairment_type = COALESCE(NULLIF(?, ''), impairment_type),
                            wheelchair_status = COALESCE(NULLIF(?, ''), wheelchair_status),
                            photo_path = COALESCE(NULLIF(?, ''), photo_path),
                            photo_status = IF(? != '', 'verified', photo_status),
                            passport_file = COALESCE(NULLIF(?, ''), passport_file),
                            medical_certificate = COALESCE(NULLIF(?, ''), medical_certificate)
                            WHERE id = ?");
                        
                        $upAthlete->execute([
                            $req['requested_email'],
                            $req['requested_phone'],
                            $req['requested_address'],
                            $req['requested_pincode'],
                            $req['requested_state'] ?? null,
                            $req['requested_state'] ?? null,
                            $req['requested_kit_tshirt'] ?? null,
                            $req['requested_kit_tracksuit'] ?? null,
                            $req['requested_kit_shoe'] ?? null,
                            $req['requested_aadhaar'] ?? null,
                            $req['requested_impairment_type'] ?? null,
                            $req['requested_wheelchair_status'] ?? null,
                            $req['requested_photo_path'],
                            $req['requested_photo_path'],
                            $req['requested_passport_file'] ?? null,
                            $req['requested_medical_certificate'] ?? null,
                            $memberId
                        ]);
                    } else {
                        // Update live official details
                        $upOfficial = $pdo->prepare("UPDATE officials SET 
                            email = COALESCE(NULLIF(?, ''), email),
                            phone = COALESCE(NULLIF(?, ''), phone),
                            address = COALESCE(NULLIF(?, ''), address),
                            pincode = COALESCE(NULLIF(?, ''), pincode),
                            state = COALESCE(NULLIF(?, ''), state),
                            photo_path = COALESCE(NULLIF(?, ''), photo_path),
                            photo_status = IF(? != '', 'verified', photo_status),
                            passport_file = COALESCE(NULLIF(?, ''), passport_file)
                            WHERE id = ?");
                        
                        $upOfficial->execute([
                            $req['requested_email'],
                            $req['requested_phone'],
                            $req['requested_address'],
                            $req['requested_pincode'],
                            $req['requested_state'] ?? null,
                            $req['requested_photo_path'],
                            $req['requested_photo_path'],
                            $req['requested_passport_file'] ?? null,
                            $memberId
                        ]);
                    }


                    // Update request status
                    $upReq = $pdo->prepare("UPDATE profile_update_requests SET status = 'approved', reviewed_by = ?, reviewed_at = NOW(), review_notes = ? WHERE id = ?");
                    $upReq->execute([$_SESSION['user_id'], $notes, $requestId]);

                    // Fetch profile info for email notification
                    $regNo = '';
                    $name = '';
                    if ($memberType === 'athlete') {
                        $stmt = $pdo->prepare("SELECT regn_no, full_name FROM athletes WHERE id = ?");
                        $stmt->execute([$memberId]);
                        $mRow = $stmt->fetch();
                        if ($mRow) {
                            $regNo = $mRow['regn_no'];
                            $name = $mRow['full_name'];
                        }
                    } else {
                        $stmt = $pdo->prepare("SELECT official_reg_no, name FROM officials WHERE id = ?");
                        $stmt->execute([$memberId]);
                        $mRow = $stmt->fetch();
                        if ($mRow) {
                            $regNo = $mRow['official_reg_no'];
                            $name = $mRow['name'];
                        }
                    }

                    $pdo->commit();

                    // Send email to newly requested email address
                    sendUpdateApprovalEmail($req['requested_email'], $name, $regNo, $memberType);

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

                if ($app['status'] !== 'pending') {
                    throw new Exception("This application has already been processed (current status: " . $app['status'] . ").");
                }

                // Retrieve and validate assigned classification & NSRS ID if approving
                $assignedClass = '';
                $nsrsId = '';
                if ($action === 'approve_link' || $action === 'approve_new') {
                    $assignedClass = trim($_POST['assigned_classification'] ?? '');
                    if (!in_array($assignedClass, ['BC1', 'BC2', 'BC3', 'BC4'])) {
                        throw new Exception("Please select a valid Boccia category (BC1-BC4) for approval.");
                    }
                    $nsrsId = trim($_POST['nsrs_id'] ?? '');
                    if (empty($nsrsId)) {
                        throw new Exception("NSRS ID is compulsory to approve an athlete registration.");
                    }
                    $chkUnique = isNsrsIdUnique($pdo, $nsrsId, $action === 'approve_link' ? (int)($_POST['existing_id'] ?? 0) : null, null);
                    if ($chkUnique !== true) {
                        throw new Exception($chkUnique);
                    }
                }

                if ($action === 'reject') {
                    $notes = isset($_POST['review_notes']) ? trim($_POST['review_notes']) : '';
                    $up = $pdo->prepare("UPDATE athlete_applications SET status = 'rejected', review_notes = ? WHERE id = ?");
                    $up->execute([$notes, $applicationId]);
                    logAction($pdo, "Rejected Athlete Application", "athlete_applications", $applicationId, "Name: {$app['full_name']} | Notes: {$notes}");
                    sendRejectionEmail($app['email'], $app['full_name'], $app['reference_id'] ?? "BSFI-ATH-{$applicationId}", 'athlete', $notes);
                    $message = "<div class='alert alert-success border-0 p-3 mb-4 rounded-3' style='background-color:#ECFDF5; color:#065F46;'>Application for <strong>" . htmlspecialchars($app['full_name']) . "</strong> rejected successfully. Notification email sent to applicant.</div>";
                } elseif ($action === 'approve_link') {
                    $pdo->beginTransaction();
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
                        aadhaar = COALESCE(?, aadhaar), status = 'approved', nsrs_id = ?,
                        photo_status = IF(? != '' OR photo_path IS NOT NULL, 'verified', photo_status),
                        father_name = ?, mother_name = ?, age_category = ?, impairment_type = ?,
                        address = ?, pincode = ?, kit_tshirt = ?, kit_tracksuit = ?, kit_shoe = ?, medical_certificate = COALESCE(?, medical_certificate), passport_file = COALESCE(?, passport_file) 
                        WHERE id = ?");
                    
                    $genderFormatted = strtoupper($app['gender']);
                    if (!in_array($genderFormatted, ['MALE', 'FEMALE', 'OTHER'])) {
                        $genderFormatted = 'MALE';
                    }

                    $upAthlete->execute([
                        $app['full_name'], $genderFormatted, $app['dob'], $app['phone'], $app['email'],
                        $app['state'], $app['district'], $assignedClass, $app['wheelchair_status'],
                        $app['photo_path'], $app['receipt_path'], $app['aadhaar'], $nsrsId, $app['photo_path'],
                        $app['father_name'], $app['mother_name'], $app['age_category'], $app['impairment_type'],
                        $app['address'], $app['pincode'], $app['kit_tshirt'], $app['kit_tracksuit'], $app['kit_shoe'], $app['medical_certificate'], $app['receipt_path'],
                        $existingId
                    ]);

                    $upApp = $pdo->prepare("UPDATE athlete_applications SET status = 'approved', existing_athlete_id = ? WHERE id = ?");
                    $upApp->execute([$existingId, $applicationId]);

                    $hist = $pdo->prepare("INSERT INTO athlete_status_history (athlete_id, old_status, new_status, changed_by, remarks) VALUES (?, ?, 'approved', ?, ?)");
                    $hist->execute([$existingId, $existing['status'], $_SESSION['user_id'], "Linked and approved from application ID: $applicationId | NSRS ID: $nsrsId"]);

                    $pdo->commit();

                    logAction($pdo, "Linked & Approved Athlete Application", "athletes", $existingId, "Name: {$app['full_name']} | REGN_NO: {$existing['regn_no']} | NSRS_ID: $nsrsId");
                    sendApprovalEmail($app['email'], $app['full_name'], $existing['regn_no'], 'athlete');
                    $message = "<div class='alert alert-success border-0 p-3 mb-4 rounded-3' style='background-color:#ECFDF5; color:#065F46;'>Application linked to existing Athlete <strong>" . htmlspecialchars($existing['regn_no']) . "</strong> and approved successfully with NSRS ID: <strong>" . htmlspecialchars($nsrsId) . "</strong>.</div>";
                } elseif ($action === 'approve_new') {
                    $pdo->beginTransaction();

                    // Mutex lock to prevent concurrent generation issues
                    $pdo->query("SELECT athlete_last_no FROM registration_sequences WHERE id = 1 FOR UPDATE");

                    // Determine highest existing sequential registration number (approved live records only)
                    $maxAthStmt = $pdo->query("SELECT MAX(CAST(regn_no AS UNSIGNED)) FROM athletes WHERE status = 'approved' AND deleted_at IS NULL AND regn_no REGEXP '^[0-9]+$'");
                    $maxAthNo = (int)$maxAthStmt->fetchColumn();
                    if ($maxAthNo < 99) {
                        $maxAthNo = 99; // Preserve legacy numbering strategy
                    }
                    $nextNo = $maxAthNo + 1;

                    $upSeq = $pdo->prepare("UPDATE registration_sequences SET athlete_last_no = ? WHERE id = 1");
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
                        (regn_no, nsrs_id, full_name, gender, dob, mobile, email, state, district, classification, representing_for, state_association_id, wheelchair_status, photo_path, receipt_path, status, aadhaar, digilocker_imported, photo_status,
                         father_name, mother_name, age_category, impairment_type, address, pincode, kit_tshirt, kit_tracksuit, kit_shoe, medical_certificate, passport_file) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved', ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    $hasPhoto = !empty($app['photo_path']) ? 'verified' : 'missing';
                    $insAthlete->execute([
                        $regnNo, $nsrsId, $app['full_name'], $genderFormatted, $app['dob'], $app['phone'], $app['email'],
                        $app['state'], $app['district'], $assignedClass, $app['state'], $assocId,
                        $app['wheelchair_status'], $app['photo_path'], $app['receipt_path'], $app['aadhaar'], $hasPhoto,
                        $app['father_name'], $app['mother_name'], $app['age_category'], $app['impairment_type'],
                        $app['address'], $app['pincode'], $app['kit_tshirt'], $app['kit_tracksuit'], $app['kit_shoe'], $app['medical_certificate'], $app['receipt_path']
                    ]);
                    $newAthleteId = $pdo->lastInsertId();

                    $upApp = $pdo->prepare("UPDATE athlete_applications SET status = 'approved', existing_athlete_id = ? WHERE id = ?");
                    $upApp->execute([$newAthleteId, $applicationId]);

                    $hist = $pdo->prepare("INSERT INTO athlete_status_history (athlete_id, old_status, new_status, changed_by, remarks) VALUES (?, NULL, 'approved', ?, ?)");
                    $hist->execute([$newAthleteId, $_SESSION['user_id'], "Newly approved athlete from application ID: $applicationId | NSRS ID: $nsrsId"]);

                    $pdo->commit();

                    logAction($pdo, "Approved New Athlete Application", "athletes", $newAthleteId, "Name: {$app['full_name']} | Generated REGN_NO: $regnNo | NSRS_ID: $nsrsId");
                    sendApprovalEmail($app['email'], $app['full_name'], $regnNo, 'athlete');
                    $message = "<div class='alert alert-success border-0 p-3 mb-4 rounded-3' style='background-color:#ECFDF5; color:#065F46;'>Application approved successfully! Athlete REGN_NO: <strong>$regnNo</strong> | NSRS ID: <strong>" . htmlspecialchars($nsrsId) . "</strong></div>";
                }
            } elseif ($type === 'official') {
                // Fetch application details
                $appStmt = $pdo->prepare("SELECT * FROM official_applications WHERE id = ?");
                $appStmt->execute([$applicationId]);
                $app = $appStmt->fetch(PDO::FETCH_ASSOC);

                if (!$app) {
                    throw new Exception("Official application not found.");
                }

                if ($app['status'] !== 'pending') {
                    throw new Exception("This application has already been processed (current status: " . $app['status'] . ").");
                }

                $nsrsId = '';
                if ($action === 'approve_link' || $action === 'approve_new') {
                    $nsrsId = trim($_POST['nsrs_id'] ?? '');
                    if (empty($nsrsId)) {
                        throw new Exception("NSRS ID is compulsory to approve an official registration.");
                    }
                    $chkUnique = isNsrsIdUnique($pdo, $nsrsId, null, $action === 'approve_link' ? (int)($_POST['existing_id'] ?? 0) : null);
                    if ($chkUnique !== true) {
                        throw new Exception($chkUnique);
                    }
                }

                if ($action === 'reject') {
                    $notes = isset($_POST['review_notes']) ? trim($_POST['review_notes']) : '';
                    $up = $pdo->prepare("UPDATE official_applications SET status = 'rejected', review_notes = ? WHERE id = ?");
                    $up->execute([$notes, $applicationId]);
                    logAction($pdo, "Rejected Official Application", "official_applications", $applicationId, "Name: {$app['full_name']} | Notes: {$notes}");
                    sendRejectionEmail($app['email'], $app['full_name'], $app['reference_id'] ?? "BSFI-OFF-{$applicationId}", 'official', $notes);
                    $message = "<div class='alert alert-success border-0 p-3 mb-4 rounded-3' style='background-color:#ECFDF5; color:#065F46;'>Official application for <strong>" . htmlspecialchars($app['full_name']) . "</strong> rejected successfully. Notification email sent to applicant.</div>";
                } elseif ($action === 'approve_link') {
                    $pdo->beginTransaction();
                    $existingId = (int)$_POST['existing_id'];
                    $existStmt = $pdo->prepare("SELECT * FROM officials WHERE id = ?");
                    $existStmt->execute([$existingId]);
                    $existing = $existStmt->fetch(PDO::FETCH_ASSOC);

                    if (!$existing) {
                        throw new Exception("Existing official profile not found for linking.");
                    }

                    // Update existing official details
                    $upOfficial = $pdo->prepare("UPDATE officials SET 
                        name = ?, category = ?, role = ?, gender = ?, dob = ?, father_name = ?, 
                        state = ?, aadhaar = COALESCE(?, aadhaar), phone = ?, email = ?, 
                        address = ?, pincode = ?, kit_tshirt = ?, kit_tracksuit = ?, kit_shoe = ?, 
                        education_qualification = ?, para_sports_experience = ?, classifier_type = ?, 
                        classifier_type_other = ?, passport_file = COALESCE(?, passport_file),
                        photo_path = COALESCE(?, photo_path), receipt_path = COALESCE(?, receipt_path), status = 'approved', nsrs_id = ?,
                        photo_status = IF(? != '' OR photo_path IS NOT NULL, 'verified', photo_status) 
                        WHERE id = ?");
                    
                    $upOfficial->execute([
                        $app['full_name'], $app['category'], $app['role'], $app['gender'], $app['dob'], $app['father_name'],
                        $app['state'], $app['aadhaar'], $app['phone'], $app['email'],
                        $app['address'], $app['pincode'], $app['kit_tshirt'], $app['kit_tracksuit'], $app['kit_shoe'],
                        $app['education_qualification'], $app['para_sports_experience'], $app['classifier_type'],
                        $app['classifier_type_other'], $app['passport_file'],
                        $app['photo_path'], $app['receipt_path'], $nsrsId, $app['photo_path'], $existingId
                    ]);

                    $upApp = $pdo->prepare("UPDATE official_applications SET status = 'approved', existing_official_id = ? WHERE id = ?");
                    $upApp->execute([$existingId, $applicationId]);

                    $pdo->commit();

                    logAction($pdo, "Linked & Approved Official Application", "officials", $existingId, "Name: {$app['full_name']} | Official ID: {$existing['official_reg_no']} | NSRS_ID: $nsrsId");
                    sendApprovalEmail($app['email'], $app['full_name'], $existing['official_reg_no'], 'official');
                    $message = "<div class='alert alert-success border-0 p-3 mb-4 rounded-3' style='background-color:#ECFDF5; color:#065F46;'>Application linked to existing Official <strong>" . htmlspecialchars($existing['official_reg_no']) . "</strong> and approved successfully with NSRS ID: <strong>" . htmlspecialchars($nsrsId) . "</strong>.</div>";
                } elseif ($action === 'approve_new') {
                    $pdo->beginTransaction();

                    // Mutex lock to prevent concurrent generation issues
                    $pdo->query("SELECT official_last_no FROM registration_sequences WHERE id = 1 FOR UPDATE");

                    // Determine the highest approved live official suffix (ignoring prefix 'OF-', approved live records only)
                    $maxOffStmt = $pdo->query("SELECT MAX(CAST(SUBSTRING(official_reg_no, 4) AS UNSIGNED)) FROM officials WHERE official_reg_no LIKE 'OF-%'");
                    $maxOffNo = (int)$maxOffStmt->fetchColumn();
                    $nextNo = $maxOffNo + 1;

                    $upSeq = $pdo->prepare("UPDATE registration_sequences SET official_last_no = ?");
                    $upSeq->execute([$nextNo]);

                    $officialId = "OF-" . str_pad($nextNo, 4, '0', STR_PAD_LEFT);

                    $insOfficial = $pdo->prepare("INSERT INTO officials 
                        (official_reg_no, nsrs_id, name, category, role, gender, dob, father_name, state, aadhaar, phone, email, address, pincode, kit_tshirt, kit_tracksuit, kit_shoe, education_qualification, para_sports_experience, classifier_type, classifier_type_other, passport_file, photo_path, receipt_path, status, photo_status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved', ?)");
                    
                    $hasPhoto = !empty($app['photo_path']) ? 'verified' : 'missing';
                    $insOfficial->execute([
                        $officialId, $nsrsId, $app['full_name'], $app['category'], $app['role'], $app['gender'], $app['dob'], $app['father_name'],
                        $app['state'], $app['aadhaar'], $app['phone'], $app['email'], $app['address'], $app['pincode'],
                        $app['kit_tshirt'], $app['kit_tracksuit'], $app['kit_shoe'], $app['education_qualification'], $app['para_sports_experience'],
                        $app['classifier_type'], $app['classifier_type_other'], $app['passport_file'], $app['photo_path'], $app['receipt_path'], $hasPhoto
                    ]);
                    $newOfficialId = $pdo->lastInsertId();

                    $upApp = $pdo->prepare("UPDATE official_applications SET status = 'approved', existing_official_id = ? WHERE id = ?");
                    $upApp->execute([$newOfficialId, $applicationId]);

                    $pdo->commit();

                    logAction($pdo, "Approved New Official Application", "officials", $newOfficialId, "Name: {$app['full_name']} | Generated ID: $officialId | NSRS_ID: $nsrsId");
                    sendApprovalEmail($app['email'], $app['full_name'], $officialId, 'official');
                    $message = "<div class='alert alert-success border-0 p-3 mb-4 rounded-3' style='background-color:#ECFDF5; color:#065F46;'>Application approved successfully! Official ID: <strong>$officialId</strong> | NSRS ID: <strong>" . htmlspecialchars($nsrsId) . "</strong></div>";
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

// Fetch rejected applications for log view
$rejectedAthletes = $pdo->query("SELECT * FROM athlete_applications WHERE status = 'rejected' ORDER BY updated_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$rejectedOfficials = $pdo->query("SELECT * FROM official_applications WHERE status = 'rejected' ORDER BY updated_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$totalRejectedCount = count($rejectedAthletes) + count($rejectedOfficials);

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
        <div style="display:flex; gap:1rem; margin-bottom:2rem; border-bottom:2px solid #E2E8F0; padding-bottom:1px; flex-wrap:wrap;">
            <a href="?tab=athletes" style="text-decoration:none; padding:1rem 2rem; font-family:'Outfit',sans-serif; font-weight:700; font-size:1.1rem; border-bottom:3px solid <?php echo $tab === 'athletes' ? 'var(--bsfi-green)' : 'transparent'; ?>; color:<?php echo $tab === 'athletes' ? 'var(--bsfi-green)' : 'var(--text-secondary)'; ?>; transition:all 0.3s ease;">
                Athletes Queue (<?php echo count($athletesQueue); ?>)
            </a>
            <a href="?tab=officials" style="text-decoration:none; padding:1rem 2rem; font-family:'Outfit',sans-serif; font-weight:700; font-size:1.1rem; border-bottom:3px solid <?php echo $tab === 'officials' ? 'var(--bsfi-green)' : 'transparent'; ?>; color:<?php echo $tab === 'officials' ? 'var(--bsfi-green)' : 'var(--text-secondary)'; ?>; transition:all 0.3s ease;">
                Officials Queue (<?php echo count($officialsQueue); ?>)
            </a>
            <a href="?tab=profile_updates" style="text-decoration:none; padding:1rem 2rem; font-family:'Outfit',sans-serif; font-weight:700; font-size:1.1rem; border-bottom:3px solid <?php echo $tab === 'profile_updates' ? 'var(--bsfi-green)' : 'transparent'; ?>; color:<?php echo $tab === 'profile_updates' ? 'var(--bsfi-green)' : 'var(--text-secondary)'; ?>; transition:all 0.3s ease;">
                Profile Updates (<?php echo count($profileUpdatesQueue); ?>)
            </a>
            <a href="?tab=rejected" style="text-decoration:none; padding:1rem 2rem; font-family:'Outfit',sans-serif; font-weight:700; font-size:1.1rem; border-bottom:3px solid <?php echo $tab === 'rejected' ? '#dc2626' : 'transparent'; ?>; color:<?php echo $tab === 'rejected' ? '#dc2626' : 'var(--text-secondary)'; ?>; transition:all 0.3s ease;">
                Rejected Log (<?php echo $totalRejectedCount; ?>)
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
                                    <div><strong>Aadhaar No:</strong> 
                                        <?php
                                            $isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
                                            $rawAadhaar = $app['aadhaar'] ?? '';
                                            if (strlen($rawAadhaar) === 12 && ctype_digit($rawAadhaar)) {
                                                $maskedAadhaar = 'XXXX-XXXX-' . substr($rawAadhaar, -4);
                                                if ($isAdmin) {
                                                    echo '<span id="aadhaar-ath-' . $app['id'] . '" class="fw-bold text-dark" style="font-family: monospace;" data-full="' . htmlspecialchars($rawAadhaar) . '" data-masked="' . htmlspecialchars($maskedAadhaar) . '">' . htmlspecialchars($maskedAadhaar) . '</span>';
                                                    echo ' <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none" onclick="toggleAadhaarReg(\'ath\', ' . $app['id'] . ')" style="font-size:0.75rem; border:none; background:none; vertical-align:middle;"><i id="aadhaar-ath-icon-' . $app['id'] . '" class="fa-solid fa-eye text-primary"></i> <span id="aadhaar-ath-lbl-' . $app['id'] . '">Show</span></button>';
                                                } else {
                                                    echo '<span class="fw-bold text-dark" style="font-family: monospace;">' . htmlspecialchars($maskedAadhaar) . '</span>';
                                                }
                                            } else {
                                                echo htmlspecialchars($rawAadhaar ?: 'N/A');
                                            }
                                        ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Actions Row -->
                            <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid #E2E8F0; padding-top:1.5rem;">
                                <div style="display:flex; gap:1rem;">
                                    <?php if (!empty($app['photo_path'])): ?>
                                        <a href="../<?php echo htmlspecialchars($app['photo_path']); ?>" target="_blank" class="admin-btn admin-btn-outline">View Photo</a>
                                    <?php endif; ?>
                                    <?php if (!empty($app['receipt_path'])): ?>
                                        <a href="download-doc.php?file=<?php echo urlencode($app['receipt_path']); ?>" target="_blank" class="admin-btn admin-btn-outline">View ID Proof</a>
                                    <?php endif; ?>
                                    <?php if (!empty($app['medical_certificate'])): ?>
                                        <a href="download-doc.php?file=<?php echo urlencode($app['medical_certificate']); ?>" target="_blank" class="admin-btn admin-btn-outline">View Med Cert</a>
                                    <?php endif; ?>
                                </div>
                                <form action="registrations.php?tab=athletes" method="POST" style="display:flex; gap:0.5rem; margin:0; flex-wrap:wrap; align-items:center;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="type" value="athlete">
                                    <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                    
                                    <select name="assigned_classification" class="form-select rounded-pill px-3 py-1" style="font-size:0.85rem; width: auto; max-width: 140px; border: 2px solid rgba(22, 41, 90, 0.1);">
                                        <option value="">Category</option>
                                        <option value="BC1">BC1</option>
                                        <option value="BC2">BC2</option>
                                        <option value="BC3">BC3</option>
                                        <option value="BC4">BC4</option>
                                    </select>
                                    <input type="text" name="nsrs_id" class="admin-input" placeholder="NSRS ID (Approval)..." style="font-size:0.85rem; width: 170px; border-radius: 20px; padding: 0.3rem 0.75rem; border: 2px solid #081B4B;">
                                    <input type="text" name="review_notes" class="admin-input" placeholder="Rejection reason / comments..." style="font-size:0.85rem; width: 200px; border-radius: 20px; padding: 0.3rem 0.75rem;">
                                    <?php if ($app['possible_duplicate'] && $app['existing_athlete_id']): ?>
                                        <input type="hidden" name="existing_id" value="<?php echo $app['existing_athlete_id']; ?>">
                                        <button type="submit" name="action" value="approve_link" class="admin-btn admin-btn-warning">Approve &amp; Link Profile</button>
                                        <button type="submit" name="action" value="approve_new" class="admin-btn admin-btn-primary">Approve as New Athlete</button>
                                    <?php else: ?>
                                        <button type="submit" name="action" value="approve_new" class="admin-btn admin-btn-primary">Approve Registration</button>
                                    <?php endif; ?>
                                    <button type="submit" name="action" value="reject" class="admin-btn admin-btn-danger" formnovalidate onclick="return confirm('Are you sure you want to reject this athlete application? A rejection email will be sent.');">Reject</button>
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
                                    <div><strong>Category:</strong> <span class="admin-badge admin-badge-info"><?php echo htmlspecialchars($app['category'] ?: $app['role']); ?></span></div>
                                    <div><strong>Gender:</strong> <?php echo htmlspecialchars($app['gender']); ?></div>
                                    <div><strong>Date of Birth:</strong> <?php echo htmlspecialchars($app['dob']); ?></div>
                                    <div><strong>Phone:</strong> <?php echo htmlspecialchars($app['phone']); ?></div>
                                    <div><strong>Email:</strong> <?php echo htmlspecialchars($app['email']); ?></div>
                                    <div><strong>State:</strong> <?php echo htmlspecialchars($app['state']); ?></div>
                                    <div><strong>Kit Sizes:</strong> T:<?php echo htmlspecialchars($app['kit_tshirt']); ?>/Tr:<?php echo htmlspecialchars($app['kit_tracksuit']); ?>/S:<?php echo htmlspecialchars($app['kit_shoe']); ?></div>
                                    <?php if(!empty($app['education_qualification'])): ?>
                                        <div style="grid-column: 1 / -1;"><strong>Education Qualification:</strong> <?php echo htmlspecialchars($app['education_qualification']); ?></div>
                                    <?php endif; ?>
                                    <?php if(!empty($app['classifier_type'])): ?>
                                        <div><strong>Classifier Type:</strong> <?php echo htmlspecialchars($app['classifier_type'] === 'Other' ? "Other ({$app['classifier_type_other']})" : $app['classifier_type']); ?></div>
                                    <?php endif; ?>
                                    <?php if(!empty($app['para_sports_experience'])): ?>
                                        <div style="grid-column: 1 / -1;"><strong>Experience in Para Sports:</strong> <?php echo nl2br(htmlspecialchars($app['para_sports_experience'])); ?></div>
                                    <?php endif; ?>
                                    <div><strong>Aadhaar No:</strong> 
                                        <?php
                                            $isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
                                            $rawAadhaar = $app['aadhaar'] ?? '';
                                            if (strlen($rawAadhaar) === 12 && ctype_digit($rawAadhaar)) {
                                                $maskedAadhaar = 'XXXX-XXXX-' . substr($rawAadhaar, -4);
                                                if ($isAdmin) {
                                                    echo '<span id="aadhaar-off-' . $app['id'] . '" class="fw-bold text-dark" style="font-family: monospace;" data-full="' . htmlspecialchars($rawAadhaar) . '" data-masked="' . htmlspecialchars($maskedAadhaar) . '">' . htmlspecialchars($maskedAadhaar) . '</span>';
                                                    echo ' <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none" onclick="toggleAadhaarReg(\'off\', ' . $app['id'] . ')" style="font-size:0.75rem; border:none; background:none; vertical-align:middle;"><i id="aadhaar-off-icon-' . $app['id'] . '" class="fa-solid fa-eye text-primary"></i> <span id="aadhaar-off-lbl-' . $app['id'] . '">Show</span></button>';
                                                } else {
                                                    echo '<span class="fw-bold text-dark" style="font-family: monospace;">' . htmlspecialchars($maskedAadhaar) . '</span>';
                                                }
                                            } else {
                                                echo htmlspecialchars($rawAadhaar ?: 'N/A');
                                            }
                                        ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Actions Row -->
                            <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid #E2E8F0; padding-top:1.5rem;">
                                <div style="display:flex; gap:1rem;">
                                    <?php if (!empty($app['photo_path'])): ?>
                                        <a href="../<?php echo htmlspecialchars($app['photo_path']); ?>" target="_blank" class="admin-btn admin-btn-outline">View Photo</a>
                                    <?php endif; ?>
                                    <?php if (!empty($app['receipt_path'])): ?>
                                        <a href="download-doc.php?file=<?php echo urlencode($app['receipt_path']); ?>" target="_blank" class="admin-btn admin-btn-outline">View ID Proof</a>
                                    <?php endif; ?>
                                    <?php if (!empty($app['passport_file'])): ?>
                                        <a href="download-doc.php?file=<?php echo urlencode($app['passport_file']); ?>" target="_blank" class="admin-btn admin-btn-outline">View Passport Booklet</a>
                                    <?php endif; ?>
                                </div>
                                <form action="registrations.php?tab=officials" method="POST" style="display:flex; gap:0.5rem; margin:0; flex-wrap:wrap; align-items:center;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="type" value="official">
                                    <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                    
                                    <input type="text" name="nsrs_id" class="admin-input" placeholder="NSRS ID (Approval)..." style="font-size:0.85rem; width: 170px; border-radius: 20px; padding: 0.3rem 0.75rem; border: 2px solid #081B4B;">
                                    <input type="text" name="review_notes" class="admin-input" placeholder="Rejection reason / comments..." style="font-size:0.85rem; width: 200px; border-radius: 20px; padding: 0.3rem 0.75rem;">
                                    <?php if ($app['possible_duplicate'] && $app['existing_official_id']): ?>
                                        <input type="hidden" name="existing_id" value="<?php echo $app['existing_official_id']; ?>">
                                        <button type="submit" name="action" value="approve_link" class="admin-btn admin-btn-warning">Approve &amp; Link Profile</button>
                                        <button type="submit" name="action" value="approve_new" class="admin-btn admin-btn-primary">Approve as New Official</button>
                                    <?php else: ?>
                                        <button type="submit" name="action" value="approve_new" class="admin-btn admin-btn-primary">Approve Registration</button>
                                    <?php endif; ?>
                                    <button type="submit" name="action" value="reject" class="admin-btn admin-btn-danger" onclick="return confirm('Are you sure you want to reject this official application? A rejection email will be sent.');">Reject</button>
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
                                        
                                        <?php if (!empty($req['requested_aadhaar'])): ?>
                                            <div><strong>Aadhaar Number:</strong></div><div style="color:var(--bsfi-green); font-weight:700; font-family:monospace;"><?php echo htmlspecialchars($req['requested_aadhaar']); ?></div>
                                        <?php endif; ?>

                                        <?php if (!empty($req['requested_kit_tshirt']) || !empty($req['requested_kit_tracksuit']) || !empty($req['requested_kit_shoe'])): ?>
                                            <div><strong>Kit Sizes:</strong></div>
                                            <div style="color:var(--bsfi-green); font-weight:600;">
                                                T-Shirt: <?php echo htmlspecialchars($req['requested_kit_tshirt'] ?: 'N/A'); ?> | 
                                                Track: <?php echo htmlspecialchars($req['requested_kit_tracksuit'] ?: 'N/A'); ?> | 
                                                Shoes: <?php echo htmlspecialchars($req['requested_kit_shoe'] ?: 'N/A'); ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($req['requested_impairment_type'])): ?>
                                            <div><strong>Impairment Type:</strong></div><div style="color:var(--bsfi-green); font-weight:600;"><?php echo htmlspecialchars($req['requested_impairment_type']); ?></div>
                                        <?php endif; ?>

                                        <?php if (!empty($req['requested_wheelchair_status'])): ?>
                                            <div><strong>Wheelchair Status:</strong></div><div style="color:var(--bsfi-green); font-weight:600;"><?php echo htmlspecialchars($req['requested_wheelchair_status']); ?></div>
                                        <?php endif; ?>

                                        <?php if (!empty($req['requested_passport_file'])): ?>
                                            <div><strong>Passport / Identity File:</strong></div>
                                            <div><a href="../<?php echo htmlspecialchars($req['requested_passport_file']); ?>" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:0.8rem;"><i class="fa-solid fa-file-pdf me-1"></i>View Document</a></div>
                                        <?php endif; ?>

                                        <?php if (!empty($req['requested_medical_certificate'])): ?>
                                            <div><strong>Medical Certificate:</strong></div>
                                            <div><a href="../<?php echo htmlspecialchars($req['requested_medical_certificate']); ?>" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:0.8rem;"><i class="fa-solid fa-file-medical me-1"></i>View Certificate</a></div>
                                        <?php endif; ?>
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
            <?php elseif ($tab === 'rejected'): ?>
                <?php if ($totalRejectedCount > 0): ?>
                    <div class="admin-card">
                        <h3 class="admin-card-title" style="font-size:1.4rem; margin-bottom:1.5rem; color:#8C201C;"><i class="fa-solid fa-ban me-2"></i> Rejected Applications Log</h3>
                        <div class="table-responsive">
                            <table class="table align-middle" style="font-size:0.9rem;">
                                <thead style="background:#F8FAFC;">
                                    <tr>
                                        <th>Type</th>
                                        <th>Reference ID</th>
                                        <th>Applicant Name</th>
                                        <th>Contact Email</th>
                                        <th>Rejection Reason / Admin Comments</th>
                                        <th>Rejected Date</th>
                                        <th style="text-align:right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rejectedAthletes as $rAth): ?>
                                        <tr>
                                            <td><span class="admin-badge admin-badge-info">Athlete</span></td>
                                            <td><code style="font-weight:700; color:#081B4B;"><?php echo htmlspecialchars($rAth['reference_id'] ?? "BSFI-ATH-{$rAth['id']}"); ?></code></td>
                                            <td><strong><?php echo htmlspecialchars($rAth['full_name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($rAth['email']); ?></td>
                                            <td>
                                                <span style="color:#991b1b; font-weight:600;">
                                                    <?php echo htmlspecialchars($rAth['review_notes'] ?: 'No reason specified.'); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d M Y, h:i A', strtotime($rAth['updated_at'])); ?></td>
                                            <td style="text-align:right;">
                                                <form action="registrations.php?tab=rejected" method="POST" style="display:inline-flex; gap:0.35rem; margin:0;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                    <input type="hidden" name="type" value="athlete">
                                                    <input type="hidden" name="application_id" value="<?php echo $rAth['id']; ?>">
                                                    <button type="submit" name="action" value="reopen" class="btn btn-sm btn-outline-warning rounded-pill px-2 py-1" style="font-size:0.75rem;" title="Reopen &amp; Move back to Active Review Queue"><i class="fa-solid fa-rotate-left me-1"></i>Reopen</button>
                                                    <button type="submit" name="action" value="delete_application" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-1" style="font-size:0.75rem;" onclick="return confirm('Permanently delete this rejected application record?');" title="Permanently Delete Record"><i class="fa-solid fa-trash me-1"></i>Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php foreach ($rejectedOfficials as $rOff): ?>
                                        <tr>
                                            <td><span class="admin-badge admin-badge-warning">Official</span></td>
                                            <td><code style="font-weight:700; color:#081B4B;"><?php echo htmlspecialchars($rOff['reference_id'] ?? "BSFI-OFF-{$rOff['id']}"); ?></code></td>
                                            <td><strong><?php echo htmlspecialchars($rOff['full_name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($rOff['email']); ?></td>
                                            <td>
                                                <span style="color:#991b1b; font-weight:600;">
                                                    <?php echo htmlspecialchars($rOff['review_notes'] ?: 'No reason specified.'); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d M Y, h:i A', strtotime($rOff['updated_at'])); ?></td>
                                            <td style="text-align:right;">
                                                <form action="registrations.php?tab=rejected" method="POST" style="display:inline-flex; gap:0.35rem; margin:0;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                    <input type="hidden" name="type" value="official">
                                                    <input type="hidden" name="application_id" value="<?php echo $rOff['id']; ?>">
                                                    <button type="submit" name="action" value="reopen" class="btn btn-sm btn-outline-warning rounded-pill px-2 py-1" style="font-size:0.75rem;" title="Reopen &amp; Move back to Active Review Queue"><i class="fa-solid fa-rotate-left me-1"></i>Reopen</button>
                                                    <button type="submit" name="action" value="delete_application" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-1" style="font-size:0.75rem;" onclick="return confirm('Permanently delete this rejected application record?');" title="Permanently Delete Record"><i class="fa-solid fa-trash me-1"></i>Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="admin-card" style="text-align:center; padding: 4rem;">
                        <p style="font-size:1.15rem; color:var(--text-secondary); margin:0;">No rejected applications found in the log.</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
function toggleAadhaarReg(type, id) {
    const txt = document.getElementById('aadhaar-' + type + '-' + id);
    const icon = document.getElementById('aadhaar-' + type + '-icon-' + id);
    const lbl = document.getElementById('aadhaar-' + type + '-lbl-' + id);
    if (txt.textContent === txt.dataset.masked) {
        txt.textContent = txt.dataset.full;
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
        lbl.textContent = 'Hide';
    } else {
        txt.textContent = txt.dataset.masked;
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
        lbl.textContent = 'Show';
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
