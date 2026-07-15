<?php
// event-manager.php - Dashboard to manage event details, forms, participants, and settings
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role-check.php';
require_once __DIR__ . '/../config/app.php';

// Restricted to admin & editor
requireLogin();
if (!in_array($_SESSION['role'], ['admin', 'editor'])) {
    checkRole(['admin', 'editor']);
}

$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

// Fetch event details
$stmt = $pdo->prepare("SELECT * FROM schedules WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    die("Event not found.");
}

$page_title = "Event Manager - " . htmlspecialchars($event['discipline']);
include __DIR__ . '/../includes/header.php';

$message = '';
$active_tab = isset($_GET['tab']) ? trim($_GET['tab']) : 'overview';

// ═══════════════════════════════════════════
// POST HANDLERS
// ═══════════════════════════════════════════

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
        $message = "<div class='alert alert-danger'>Invalid CSRF Token.</div>";
    } else {
        // 1. UPDATE SETTINGS
        if (isset($_POST['update_settings'])) {
            $fee = (float)($_POST['registration_fee'] ?? 0.00);
            $deadline = !empty($_POST['registration_deadline']) ? trim($_POST['registration_deadline']) : null;
            $capacity = !empty($_POST['max_participants']) ? (int)$_POST['max_participants'] : null;
            $waitlist = isset($_POST['allow_waiting_list']) ? 1 : 0;
            $mode = trim($_POST['registration_mode'] ?? 'internal');

            try {
                $up = $pdo->prepare("UPDATE schedules SET registration_fee = ?, registration_deadline = ?, max_participants = ?, allow_waiting_list = ?, registration_mode = ? WHERE id = ?");
                $up->execute([$fee, $deadline, $capacity, $waitlist, $mode, $event_id]);
                logAction($pdo, "Updated Event Registration Settings", "schedules", $event_id);
                $message = "<div class='alert alert-success'>Event settings updated successfully.</div>";
                // Refresh event data
                $event['registration_fee'] = $fee;
                $event['registration_deadline'] = $deadline;
                $event['max_participants'] = $capacity;
                $event['allow_waiting_list'] = $waitlist;
                $event['registration_mode'] = $mode;
            } catch (PDOException $e) {
                $message = "<div class='alert alert-danger'>Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }

        // 2. ADD CUSTOM FIELD
        if (isset($_POST['add_field'])) {
            $label = trim($_POST['field_label'] ?? '');
            $type = trim($_POST['field_type'] ?? 'text');
            $required = isset($_POST['is_required']) ? 1 : 0;
            $placeholder = trim($_POST['placeholder'] ?? '');
            $help_text = trim($_POST['help_text'] ?? '');
            $options = trim($_POST['field_options'] ?? '');

            if (empty($label)) {
                $message = "<div class='alert alert-danger'>Field Label is required.</div>";
            } else {
                try {
                    $ins = $pdo->prepare("INSERT INTO event_form_fields (schedule_id, field_label, field_type, is_required, placeholder, help_text, field_options) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $ins->execute([$event_id, $label, $type, $required, $placeholder, $help_text, $options]);
                    logAction($pdo, "Added Custom Field to Event Form", "schedules", $event_id, "Field: $label");
                    $message = "<div class='alert alert-success'>Custom field <strong>" . htmlspecialchars($label) . "</strong> added.</div>";
                } catch (PDOException $e) {
                    $message = "<div class='alert alert-danger'>Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
                }
            }
        }

        // 3. EDIT CUSTOM FIELD (Deactivate/Order)
        if (isset($_POST['update_fields'])) {
            try {
                $pdo->beginTransaction();
                foreach ($_POST['fields'] as $fid => $fattr) {
                    $active = isset($fattr['is_active']) ? 1 : 0;
                    $sort = (int)($fattr['sort_order'] ?? 0);
                    $up = $pdo->prepare("UPDATE event_form_fields SET is_active = ?, sort_order = ? WHERE id = ? AND schedule_id = ?");
                    $up->execute([$active, $sort, $fid, $event_id]);
                }
                $pdo->commit();
                logAction($pdo, "Updated Custom Fields Configuration", "schedules", $event_id);
                $message = "<div class='alert alert-success'>Fields updated successfully.</div>";
            } catch (PDOException $e) {
                $pdo->rollBack();
                $message = "<div class='alert alert-danger'>Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }

        // 4. DELETE CUSTOM FIELD (Hard delete only if no answers exist, else soft-deactivate)
        if (isset($_POST['delete_field'])) {
            $fid = (int)$_POST['field_id'];
            try {
                // Check if answers exist
                $chk = $pdo->prepare("SELECT COUNT(*) FROM event_registration_answers WHERE field_id = ?");
                $chk->execute([$fid]);
                $has_answers = $chk->fetchColumn() > 0;

                if ($has_answers) {
                    // Soft deactivate
                    $up = $pdo->prepare("UPDATE event_form_fields SET is_active = 0 WHERE id = ? AND schedule_id = ?");
                    $up->execute([$fid, $event_id]);
                    $message = "<div class='alert alert-warning'>Field has response history. It has been deactivated/hidden from the registration form instead of deleted.</div>";
                } else {
                    // Hard delete
                    $del = $pdo->prepare("DELETE FROM event_form_fields WHERE id = ? AND schedule_id = ?");
                    $del->execute([$fid, $event_id]);
                    $message = "<div class='alert alert-success'>Field deleted successfully.</div>";
                }
                logAction($pdo, "Modified Event Custom Field (Deactivated/Deleted)", "schedules", $event_id);
            } catch (PDOException $e) {
                $message = "<div class='alert alert-danger'>Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }

        // 5. VERIFY PARTICIPANT REGISTRATION (APPROVE/REJECT)
        if (isset($_POST['verify_participant'])) {
            $reg_id = (int)$_POST['registration_id'];
            $p_status = trim($_POST['payment_status'] ?? 'pending');
            $r_status = trim($_POST['registration_status'] ?? 'pending');
            $remarks = trim($_POST['rejection_remarks'] ?? '');

            try {
                $pdo->beginTransaction();

                // Fetch registration snapshot & email details
                $regStmt = $pdo->prepare("SELECT * FROM event_registrations WHERE id = ?");
                $regStmt->execute([$reg_id]);
                $reg_data = $regStmt->fetch();

                if ($reg_data) {
                    $up = $pdo->prepare("UPDATE event_registrations SET payment_status = ?, registration_status = ?, rejection_remarks = ? WHERE id = ?");
                    $up->execute([$p_status, $r_status, $remarks, $reg_id]);

                    logAction($pdo, "Verified Event Participant Status", "event_registrations", $reg_id, "Reg No: {$reg_data['registration_no']} | Reg Status: $r_status | Payment Status: $p_status");
                    
                    // Dispatch Resend Notification Email
                    $subject = "Event Registration Status Update - BSFI";
                    $status_text = strtoupper($r_status);
                    $payment_text = strtoupper($p_status);
                    $remarks_text = !empty($remarks) ? "<p><strong>Notes / Remarks:</strong> " . htmlspecialchars($remarks) . "</p>" : "";

                    $htmlBody = "
                        <div style=\"font-family: sans-serif; padding: 20px; max-width: 600px; border: 1px solid #e2e8f0; border-radius: 10px;\">
                            <h2 style=\"color: #081B4B; margin-bottom: 20px;\">Boccia Sports Federation of India</h2>
                            <p>Hello <strong>" . htmlspecialchars($reg_data['snapshot_name']) . "</strong>,</p>
                            <p>Your registration status for the event has been updated:</p>
                            <table style=\"width: 100%; border-collapse: collapse; margin: 20px 0;\">
                                <tr>
                                    <td style=\"padding: 8px; border-bottom: 1px solid #e2e8f0;\"><strong>Event Registration ID:</strong></td>
                                    <td style=\"padding: 8px; border-bottom: 1px solid #e2e8f0;\">" . htmlspecialchars($reg_data['registration_no']) . "</td>
                                </tr>
                                <tr>
                                    <td style=\"padding: 8px; border-bottom: 1px solid #e2e8f0;\"><strong>Registration Status:</strong></td>
                                    <td style=\"padding: 8px; border-bottom: 1px solid #e2e8f0; color: " . ($r_status === 'approved' ? '#10B981' : '#EF4444') . "; font-weight: bold;\">{$status_text}</td>
                                </tr>
                                <tr>
                                    <td style=\"padding: 8px; border-bottom: 1px solid #e2e8f0;\"><strong>Payment Verification:</strong></td>
                                    <td style=\"padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: bold;\">{$payment_text}</td>
                                </tr>
                            </table>
                            {$remarks_text}
                            <p style=\"color: #64748b; font-size: 14px; margin-top: 20px;\">For any queries, please reply directly to this email or contact your state coordinator.</p>
                        </div>
                    ";

                    // Send email
                    $ch = curl_init('https://api.resend.com/emails');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Authorization: Bearer ' . RESEND_API_KEY,
                        'Content-Type: application/json'
                    ]);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                        'from' => 'Boccia India <noreply@bocciaindia.com>',
                        'to' => $reg_data['snapshot_email'],
                        'subject' => $subject,
                        'html' => $htmlBody
                    ]));
                    curl_exec($ch);
                    curl_close($ch);
                }

                $pdo->commit();
                $message = "<div class='alert alert-success'>Participant registration status updated and email notification dispatched.</div>";
            } catch (PDOException $e) {
                $pdo->rollBack();
                $message = "<div class='alert alert-danger'>Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
    }
}

// ═══════════════════════════════════════════
// DATA RETRIEVAL
// ═══════════════════════════════════════════

// 1. STATS
$stat_stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN registration_status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN registration_status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN registration_status = 'rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN registration_status = 'waiting_list' THEN 1 ELSE 0 END) as waiting_list
    FROM event_registrations 
    WHERE schedule_id = ?
");
$stat_stmt->execute([$event_id]);
$stats = $stat_stmt->fetch();

// Calculate remaining seats
$capacity = $event['max_participants'];
$pending_and_approved = $stats['approved'] + $stats['pending'];
$remaining_seats = $capacity ? max(0, $capacity - $pending_and_approved) : 'Unlimited';

// 2. CUSTOM FIELDS
$field_stmt = $pdo->prepare("SELECT * FROM event_form_fields WHERE schedule_id = ? ORDER BY sort_order ASC, id ASC");
$field_stmt->execute([$event_id]);
$form_fields = $field_stmt->fetchAll();

// 3. PARTICIPANTS DIRECTORY
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : '';

$sql = "SELECT er.* FROM event_registrations er WHERE er.schedule_id = ?";
$params = [$event_id];

if (!empty($search_query)) {
    $sql .= " AND (er.registration_no LIKE ? OR er.snapshot_name LIKE ? OR er.snapshot_regn_no LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
}

if (!empty($status_filter)) {
    $sql .= " AND er.registration_status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY er.created_at DESC";

$reg_stmt = $pdo->prepare($sql);
$reg_stmt->execute($params);
$registrations = $reg_stmt->fetchAll();
?>

<div class="admin-wrapper" id="main-content">
    <div class="container-fluid" style="padding: 2rem;">
        
        <!-- Header Row -->
        <div class="admin-page-title-row" style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <span class="admin-section-eyebrow">Event Operations</span>
                <h1 class="admin-page-title" style="margin: 0; font-family: 'Outfit', sans-serif; font-weight: 800; color: #081B4B; font-size: 1.85rem;">
                    Event Manager: <?php echo htmlspecialchars($event['discipline']); ?>
                </h1>
                <?php if($event['event_type']): ?>
                    <span class="badge bg-light text-primary border mt-2 text-uppercase fw-bold" style="font-size: 0.75rem;"><?php echo htmlspecialchars($event['event_type']); ?></span>
                <?php endif; ?>
            </div>
            <div style="display:flex; gap:0.50rem;">
                <a href="schedules.php" class="admin-btn admin-btn-outline" style="text-decoration: none;"><i class="fa-solid fa-arrow-left"></i> Schedules Calendar</a>
                <a href="dashboard.php" class="admin-btn admin-btn-outline" style="text-decoration: none;">Dashboard</a>
            </div>
        </div>

        <?php if (!empty($message)) echo $message; ?>

        <!-- Tabs Menu -->
        <ul class="nav nav-pills mb-4" id="eventManagerTabs" role="tablist" style="background: rgba(8, 27, 75, 0.05); padding: 0.5rem; border-radius: 12px; gap: 0.25rem; display: inline-flex; border: none;">
            <li class="nav-item" role="presentation">
                <a href="?event_id=<?php echo $event_id; ?>&tab=overview" class="nav-link py-2 px-4 rounded-3 fw-bold <?php echo ($active_tab === 'overview') ? 'active bg-navy text-white' : 'text-secondary'; ?>" style="font-family: 'Outfit', sans-serif; border: none; font-size: 0.95rem;">Overview</a>
            </li>
            <li class="nav-item" role="presentation">
                <a href="?event_id=<?php echo $event_id; ?>&tab=settings" class="nav-link py-2 px-4 rounded-3 fw-bold <?php echo ($active_tab === 'settings') ? 'active bg-navy text-white' : 'text-secondary'; ?>" style="font-family: 'Outfit', sans-serif; border: none; font-size: 0.95rem;">Settings</a>
            </li>
            <li class="nav-item" role="presentation">
                <a href="?event_id=<?php echo $event_id; ?>&tab=form_builder" class="nav-link py-2 px-4 rounded-3 fw-bold <?php echo ($active_tab === 'form_builder') ? 'active bg-navy text-white' : 'text-secondary'; ?>" style="font-family: 'Outfit', sans-serif; border: none; font-size: 0.95rem;">Form Builder</a>
            </li>
            <li class="nav-item" role="presentation">
                <a href="?event_id=<?php echo $event_id; ?>&tab=participants" class="nav-link py-2 px-4 rounded-3 fw-bold <?php echo ($active_tab === 'participants') ? 'active bg-navy text-white' : 'text-secondary'; ?>" style="font-family: 'Outfit', sans-serif; border: none; font-size: 0.95rem;">Participants</a>
            </li>
        </ul>

        <!-- Tab Contents -->
        <div class="tab-content" id="eventManagerTabsContent">
            
            <!-- 1. OVERVIEW TAB -->
            <?php if ($active_tab === 'overview'): ?>
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="admin-stat-card accent-navy h-100" style="padding: 1.5rem; border-radius: 12px;">
                            <span class="admin-stat-label">Total Submissions</span>
                            <h2 class="admin-stat-val" style="margin-top: 0.5rem; font-size: 2.2rem;"><?php echo (int)$stats['total']; ?></h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="admin-stat-card accent-green h-100" style="padding: 1.5rem; border-radius: 12px;">
                            <span class="admin-stat-label">Approved Participants</span>
                            <h2 class="admin-stat-val" style="margin-top: 0.5rem; font-size: 2.2rem; color: #10B981;"><?php echo (int)$stats['approved']; ?></h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="admin-stat-card accent-amber h-100" style="padding: 1.5rem; border-radius: 12px;">
                            <span class="admin-stat-label">Pending Verifications</span>
                            <h2 class="admin-stat-val" style="margin-top: 0.5rem; font-size: 2.2rem; color: #F59E0B;"><?php echo (int)$stats['pending']; ?></h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="admin-stat-card accent-blue h-100" style="padding: 1.5rem; border-radius: 12px;">
                            <span class="admin-stat-label">Remaining Seats</span>
                            <h2 class="admin-stat-val" style="margin-top: 0.5rem; font-size: 2.2rem;"><?php echo htmlspecialchars($remaining_seats); ?></h2>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="admin-card" style="border-radius: 16px; padding: 2rem;">
                            <h4 style="font-family:'Outfit',sans-serif; color:#081B4B; font-weight:700; margin-bottom:1.5rem; display:flex; align-items:center; gap:0.5rem;"><i class="fa-solid fa-circle-info"></i> Event Configuration Summary</h4>
                            <table class="table align-middle">
                                <tr>
                                    <td class="text-secondary fw-semibold">Registration Mode:</td>
                                    <td class="text-dark fw-bold text-capitalize"><?php echo htmlspecialchars($event['registration_mode']); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-secondary fw-semibold">Registration Fee:</td>
                                    <td class="text-dark fw-bold">₹<?php echo number_format($event['registration_fee'], 2); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-secondary fw-semibold">Capacity Limit:</td>
                                    <td class="text-dark fw-bold"><?php echo $event['max_participants'] ? htmlspecialchars($event['max_participants']) . ' participants' : 'No Limit'; ?></td>
                                </tr>
                                <tr>
                                    <td class="text-secondary fw-semibold">Registration Deadline:</td>
                                    <td class="text-dark fw-bold"><?php echo $event['registration_deadline'] ? date('d M Y, h:i A', strtotime($event['registration_deadline'])) : 'No Deadline'; ?></td>
                                </tr>
                                <tr>
                                    <td class="text-secondary fw-semibold">Allow Waiting List:</td>
                                    <td class="text-dark fw-bold"><?php echo $event['allow_waiting_list'] ? 'Yes' : 'No'; ?></td>
                                </tr>
                                <tr>
                                    <td class="text-secondary fw-semibold">Waiting List Registrations:</td>
                                    <td class="text-dark fw-bold"><span class="badge bg-warning text-dark"><?php echo (int)$stats['waiting_list']; ?></span></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="admin-card" style="border-radius: 16px; padding: 2rem;">
                            <h4 style="font-family:'Outfit',sans-serif; color:#081B4B; font-weight:700; margin-bottom:1.5rem;"><i class="fa-solid fa-list-check"></i> Registration Status Breakdown</h4>
                            <div class="d-flex flex-column gap-3">
                                <div>
                                    <div class="d-flex justify-content-between text-secondary mb-1">
                                        <span>Approved (<?php echo (int)$stats['approved']; ?>)</span>
                                        <span><?php echo $stats['total'] ? round(($stats['approved']/$stats['total'])*100) : 0; ?>%</span>
                                    </div>
                                    <div class="progress" style="height:8px;">
                                        <div class="progress-bar bg-success" style="width: <?php echo $stats['total'] ? ($stats['approved']/$stats['total'])*100 : 0; ?>%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="d-flex justify-content-between text-secondary mb-1">
                                        <span>Pending Review (<?php echo (int)$stats['pending']; ?>)</span>
                                        <span><?php echo $stats['total'] ? round(($stats['pending']/$stats['total'])*100) : 0; ?>%</span>
                                    </div>
                                    <div class="progress" style="height:8px;">
                                        <div class="progress-bar bg-warning" style="width: <?php echo $stats['total'] ? ($stats['pending']/$stats['total'])*100 : 0; ?>%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="d-flex justify-content-between text-secondary mb-1">
                                        <span>Rejected (<?php echo (int)$stats['rejected']; ?>)</span>
                                        <span><?php echo $stats['total'] ? round(($stats['rejected']/$stats['total'])*100) : 0; ?>%</span>
                                    </div>
                                    <div class="progress" style="height:8px;">
                                        <div class="progress-bar bg-danger" style="width: <?php echo $stats['total'] ? ($stats['rejected']/$stats['total'])*100 : 0; ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 2. SETTINGS TAB -->
            <?php if ($active_tab === 'settings'): ?>
                <div class="admin-card" style="border-radius: 16px; padding: 2rem; max-width: 800px;">
                    <h4 style="font-family:'Outfit',sans-serif; color:#081B4B; font-weight:700; margin-bottom:1.5rem;"><i class="fa-solid fa-sliders"></i> Adjust Registration Settings</h4>
                    <form action="?event_id=<?php echo $event_id; ?>&tab=settings" method="POST" style="display:flex; flex-direction:column; gap:1.25rem;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="update_settings" value="1">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="reg_mode" class="form-label fw-bold">Registration Mode</label>
                                <select id="reg_mode" name="registration_mode" class="admin-input">
                                    <option value="disabled" <?php echo ($event['registration_mode'] === 'disabled') ? 'selected' : ''; ?>>Disabled</option>
                                    <option value="internal" <?php echo ($event['registration_mode'] === 'internal') ? 'selected' : ''; ?>>Internal Event Registration</option>
                                    <option value="external" <?php echo ($event['registration_mode'] === 'external') ? 'selected' : ''; ?>>External URL Redirect</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="reg_fee" class="form-label fw-bold">Registration Fee (INR)</label>
                                <input type="number" step="0.01" min="0" id="reg_fee" name="registration_fee" class="admin-input" value="<?php echo htmlspecialchars($event['registration_fee']); ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="reg_deadline" class="form-label fw-bold">Registration Deadline</label>
                                <?php
                                $deadline_val = '';
                                if ($event['registration_deadline']) {
                                    $deadline_val = date('Y-m-d\TH:i', strtotime($event['registration_deadline']));
                                }
                                ?>
                                <input type="datetime-local" id="reg_deadline" name="registration_deadline" class="admin-input" value="<?php echo $deadline_val; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="max_part" class="form-label fw-bold">Maximum Participants (Capacity)</label>
                                <input type="number" min="1" id="max_part" name="max_participants" class="admin-input" value="<?php echo htmlspecialchars($event['max_participants'] ?? ''); ?>" placeholder="No limit">
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="allow_wl" name="allow_waiting_list" value="1" <?php echo $event['allow_waiting_list'] ? 'checked' : ''; ?> style="width:40px; height:20px; cursor:pointer;">
                                <label class="form-check-label fw-bold ms-2" for="allow_wl" style="cursor:pointer;">Allow Waiting List when Capacity is reached</label>
                            </div>
                        </div>

                        <div style="margin-top: 1rem;">
                            <button type="submit" class="admin-btn admin-btn-primary" style="padding:0.75rem 2rem;">Save Settings</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- 3. FORM BUILDER TAB -->
            <?php if ($active_tab === 'form_builder'): ?>
                <div class="row g-4">
                    <!-- Left: Field Config List -->
                    <div class="col-lg-7">
                        <div class="admin-card" style="border-radius: 16px; padding: 2rem;">
                            <h4 style="font-family:'Outfit',sans-serif; color:#081B4B; font-weight:700; margin-bottom:1.5rem; display:flex; justify-content:space-between; align-items:center;">
                                Custom Event Form Fields
                                <span class="badge bg-light text-secondary border fw-normal" style="font-size:0.8rem;"><?php echo count($form_fields); ?> Fields</span>
                            </h4>
                            
                            <?php if (count($form_fields) > 0): ?>
                                <form action="?event_id=<?php echo $event_id; ?>&tab=form_builder" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="update_fields" value="1">
                                    
                                    <div class="table-responsive">
                                        <table class="table align-middle">
                                            <thead>
                                                <tr>
                                                    <th>Sort</th>
                                                    <th>Label / Question</th>
                                                    <th>Type</th>
                                                    <th class="text-center">Required</th>
                                                    <th class="text-center">Visible</th>
                                                    <th class="text-end">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($form_fields as $f): ?>
                                                    <tr>
                                                        <td style="width: 80px;">
                                                            <input type="number" name="fields[<?php echo $f['id']; ?>][sort_order]" class="form-control form-control-sm text-center" value="<?php echo (int)$f['sort_order']; ?>">
                                                        </td>
                                                        <td>
                                                            <strong class="d-block text-navy"><?php echo htmlspecialchars($f['field_label']); ?></strong>
                                                            <?php if($f['field_options']): ?>
                                                                <small class="text-muted d-block" style="font-size:0.75rem;">Options: <?php echo htmlspecialchars($f['field_options']); ?></small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><span class="badge bg-light text-secondary border text-uppercase"><?php echo htmlspecialchars($f['field_type']); ?></span></td>
                                                        <td class="text-center">
                                                            <?php echo $f['is_required'] ? '<i class="fa-solid fa-circle-check text-success"></i>' : '<i class="fa-solid fa-circle-minus text-muted"></i>'; ?>
                                                        </td>
                                                        <td class="text-center">
                                                            <input type="checkbox" name="fields[<?php echo $f['id']; ?>][is_active]" value="1" <?php echo $f['is_active'] ? 'checked' : ''; ?> style="width: 16px; height: 16px; cursor:pointer;">
                                                        </td>
                                                        <td class="text-end">
                                                            <button type="submit" name="delete_field" value="1" onclick="document.getElementById('delete_fid').value = '<?php echo $f['id']; ?>'; return confirm('Are you sure you want to delete this field? If it already has responses, it will be hidden/deactivated instead.');" class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-trash"></i></button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <div class="mt-4 d-flex justify-content-between align-items-center">
                                        <button type="submit" class="admin-btn admin-btn-primary" style="padding:0.6rem 1.5rem;">Save Order & Visibility</button>
                                    </div>
                                </form>

                                <form action="?event_id=<?php echo $event_id; ?>&tab=form_builder" method="POST" id="delete-field-form" style="display:none;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="delete_field" value="1">
                                    <input type="hidden" name="field_id" id="delete_fid" value="0">
                                </form>
                            <?php else: ?>
                                <div class="text-center p-5 bg-light rounded-3 border">
                                    <p class="text-muted m-0">No custom questions added yet. Use the tool on the right to build your event form!</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Right: Add Field Form -->
                    <div class="col-lg-5">
                        <div class="admin-card" style="border-radius: 16px; padding: 2rem; background: #fafafa; border: 1px dashed #cbd5e1;">
                            <h4 style="font-family:'Outfit',sans-serif; color:#081B4B; font-weight:700; margin-bottom:1.5rem;"><i class="fa-solid fa-plus"></i> Add Custom Question</h4>
                            
                            <form action="?event_id=<?php echo $event_id; ?>&tab=form_builder" method="POST" style="display:flex; flex-direction:column; gap:1rem;">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="add_field" value="1">

                                <div class="admin-form-group">
                                    <label for="f_label" class="fw-semibold">Question / Label <span style="color:var(--danger)">*</span></label>
                                    <input type="text" id="f_label" name="field_label" class="admin-input" required placeholder="e.g. Coach's Full Name">
                                </div>

                                <div class="admin-form-group">
                                    <label for="f_type" class="fw-semibold">Field Type <span style="color:var(--danger)">*</span></label>
                                    <select id="f_type" name="field_type" class="admin-input" onchange="toggleOptionsInput()">
                                        <option value="text">Short Text</option>
                                        <option value="textarea">Long Text / Textarea</option>
                                        <option value="number">Number</option>
                                        <option value="date">Date</option>
                                        <option value="dropdown">Dropdown Options</option>
                                        <option value="radio">Radio Buttons</option>
                                        <option value="checkbox">Checkboxes</option>
                                        <option value="file">File Upload (PDF/Document)</option>
                                        <option value="image">Image Upload (JPG/PNG)</option>
                                    </select>
                                </div>

                                <div class="admin-form-group" id="options_wrapper" style="display:none;">
                                    <label for="f_options" class="fw-semibold">Options (Comma separated) <span style="color:var(--danger)">*</span></label>
                                    <input type="text" id="f_options" name="field_options" class="admin-input" placeholder="e.g. Yes, No, Maybe">
                                    <small class="text-muted d-block mt-1">Separate dropdown/radio choices with commas.</small>
                                </div>

                                <div class="admin-form-group">
                                    <label for="f_placeholder" class="fw-semibold">Placeholder Text</label>
                                    <input type="text" id="f_placeholder" name="placeholder" class="admin-input" placeholder="e.g. Enter full name">
                                </div>

                                <div class="admin-form-group">
                                    <label for="f_help" class="fw-semibold">Help Text / Subtext</label>
                                    <input type="text" id="f_help" name="help_text" class="admin-input" placeholder="e.g. Please match passport name">
                                </div>

                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="f_req" name="is_required" value="1" checked style="width: 18px; height: 18px; cursor:pointer;">
                                    <label class="form-check-label fw-bold ms-2" for="f_req" style="cursor:pointer; margin-bottom:0;">This question is required</label>
                                </div>

                                <button type="submit" class="admin-btn admin-btn-primary" style="margin-top:1rem; padding:0.6rem 0; font-weight:700;">Add Field</button>
                            </form>
                        </div>
                    </div>
                </div>

                <script>
                function toggleOptionsInput() {
                    const type = document.getElementById('f_type').value;
                    const wrapper = document.getElementById('options_wrapper');
                    if (['dropdown', 'radio', 'checkbox'].includes(type)) {
                        wrapper.style.display = 'block';
                        document.getElementById('f_options').required = true;
                    } else {
                        wrapper.style.display = 'none';
                        document.getElementById('f_options').required = false;
                    }
                }
                </script>
            <?php endif; ?>

            <!-- 4. PARTICIPANTS TAB -->
            <?php if ($active_tab === 'participants'): ?>
                <div class="admin-card" style="border-radius: 16px; padding: 2rem; margin-bottom: 0;">
                    
                    <!-- Search & Filter Bar -->
                    <form action="?event_id=<?php echo $event_id; ?>&tab=participants" method="GET" class="row g-3 mb-4 align-items-end">
                        <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                        <input type="hidden" name="tab" value="participants">
                        
                        <div class="col-md-5">
                            <label for="part-search" class="form-label fw-semibold text-secondary">Search Participants</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                                <input type="text" id="part-search" name="search" class="form-control border-start-0 py-2" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search by name, reference ID, or registration number...">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label for="part-filter" class="form-label fw-semibold text-secondary">Registration Status</label>
                            <select id="part-filter" name="status_filter" class="form-select py-2">
                                <option value="">All Statuses</option>
                                <option value="pending" <?php echo ($status_filter === 'pending') ? 'selected' : ''; ?>>Pending Approval</option>
                                <option value="approved" <?php echo ($status_filter === 'approved') ? 'selected' : ''; ?>>Approved</option>
                                <option value="rejected" <?php echo ($status_filter === 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                                <option value="waiting_list" <?php echo ($status_filter === 'waiting_list') ? 'selected' : ''; ?>>Waiting List</option>
                            </select>
                        </div>

                        <div class="col-md-4 d-flex gap-2">
                            <button type="submit" class="admin-btn admin-btn-primary" style="padding: 0.55rem 1.5rem;">Filter</button>
                            <a href="?event_id=<?php echo $event_id; ?>&tab=participants" class="admin-btn admin-btn-outline" style="padding: 0.55rem 1.5rem; text-decoration:none; text-align:center;">Clear</a>
                        </div>
                    </form>

                    <!-- Table -->
                    <?php if (count($registrations) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead style="background: rgba(8, 27, 75, 0.03);">
                                    <tr>
                                        <th>Reg ID</th>
                                        <th>Member ID</th>
                                        <th>Name</th>
                                        <th>State</th>
                                        <th class="text-center">Payment Status</th>
                                        <th class="text-center">Reg Status</th>
                                        <th>Date Submitted</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($registrations as $r): ?>
                                        <tr>
                                            <td><strong class="text-navy"><?php echo htmlspecialchars($r['registration_no']); ?></strong></td>
                                            <td><small class="text-secondary fw-semibold"><?php echo htmlspecialchars($r['snapshot_regn_no']); ?></small></td>
                                            <td>
                                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($r['snapshot_name']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($r['snapshot_email']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($r['snapshot_state']); ?></td>
                                            <td class="text-center">
                                                <?php if($r['payment_status'] === 'approved'): ?>
                                                    <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-bold">APPROVED</span>
                                                <?php elseif($r['payment_status'] === 'pending'): ?>
                                                    <span class="badge bg-warning-subtle text-warning px-3 py-2 rounded-pill fw-bold">PENDING</span>
                                                <?php elseif($r['payment_status'] === 'free'): ?>
                                                    <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill fw-bold">FREE</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill fw-bold">REJECTED</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if($r['registration_status'] === 'approved'): ?>
                                                    <span class="badge bg-success px-3 py-2 rounded-pill">APPROVED</span>
                                                <?php elseif($r['registration_status'] === 'pending'): ?>
                                                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">PENDING</span>
                                                <?php elseif($r['registration_status'] === 'waiting_list'): ?>
                                                    <span class="badge bg-info text-white px-3 py-2 rounded-pill">WAITLIST</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger px-3 py-2 rounded-pill">REJECTED</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><small class="text-muted"><?php echo date('d M Y, h:i A', strtotime($r['created_at'])); ?></small></td>
                                            <td class="text-end">
                                                <button onclick="openVerificationModal(<?php echo htmlspecialchars(json_encode($r)); ?>)" class="admin-btn admin-btn-outline btn-sm py-1 px-3">Verify</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center p-5 bg-light rounded-3 border">
                            <p class="text-muted m-0">No matching registrations found for this event calendar.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Verification Detail & Approval Modal -->
                <div id="verify-modal" class="lightbox" style="display:none; align-items:center; justify-content:center; background:rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 1000;">
                    <div class="admin-card" style="background:#FFFFFF; border:1px solid #E2E8F0; padding:2.5rem; border-radius:20px; max-width:850px; width:95%; position:relative; max-height: 90vh; overflow-y: auto; color: var(--text-primary); margin-bottom:0; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
                        <button onclick="closeVerificationModal()" style="position:absolute; top:15px; right:15px; background:none; border:none; color:var(--text-muted); font-size:1.5rem; cursor:pointer;">&times;</button>
                        <h3 style="font-family:'Outfit',sans-serif; font-size:1.6rem; margin-bottom:1.5rem; color: var(--navy); font-weight: 700;">Participant Verification</h3>
                        
                        <div class="row g-4">
                            <!-- Left: Registration snapshot details -->
                            <div class="col-md-7 border-end pe-md-4">
                                <h5 class="fw-bold mb-3 border-bottom pb-2 text-secondary">Profile Details Snapshot</h5>
                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; font-size:0.9rem;" class="mb-4">
                                    <div><strong>Name:</strong> <span id="v-name"></span></div>
                                    <div><strong>Registration No:</strong> <span id="v-reg-no"></span></div>
                                    <div><strong>Email:</strong> <span id="v-email"></span></div>
                                    <div><strong>Mobile:</strong> <span id="v-mobile"></span></div>
                                    <div><strong>DOB:</strong> <span id="v-dob"></span></div>
                                    <div><strong>Gender:</strong> <span id="v-gender"></span></div>
                                    <div><strong>State:</strong> <span id="v-state"></span></div>
                                    <div><strong>Classification:</strong> <span id="v-class"></span></div>
                                </div>

                                <h5 class="fw-bold mb-3 border-bottom pb-2 text-secondary">Custom Answers</h5>
                                <div id="v-custom-answers" class="mb-4" style="font-size: 0.9rem;">
                                    <!-- Dynamic custom responses inserted via JS -->
                                </div>

                                <h5 class="fw-bold mb-3 border-bottom pb-2 text-secondary">Payment Information</h5>
                                <div style="font-size: 0.9rem;" class="mb-2">
                                    <div><strong>Transaction Reference No:</strong> <span id="v-tx-ref"></span></div>
                                </div>
                                <div id="v-receipt-container" style="margin-top: 1rem;">
                                    <!-- Receipt download link/image loaded here -->
                                </div>
                            </div>

                            <!-- Right: Verification Decisions form -->
                            <div class="col-md-5 ps-md-4">
                                <h5 class="fw-bold mb-3 text-secondary">Administrative Actions</h5>
                                <form action="?event_id=<?php echo $event_id; ?>&tab=participants" method="POST" style="display:flex; flex-direction:column; gap:1.25rem;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="verify_participant" value="1">
                                    <input type="hidden" name="registration_id" id="v-id-input" value="0">

                                    <div class="admin-form-group">
                                        <label for="v-payment-status" class="fw-semibold mb-1">Verify Payment Status</label>
                                        <select id="v-payment-status" name="payment_status" class="admin-input">
                                            <option value="pending">Pending Verification</option>
                                            <option value="approved">Approved / Received</option>
                                            <option value="rejected">Rejected / Invalid Transfer</option>
                                            <option value="free">Free Event / Not Required</option>
                                        </select>
                                    </div>

                                    <div class="admin-form-group">
                                        <label for="v-reg-status" class="fw-semibold mb-1">Verify Registration Status</label>
                                        <select id="v-reg-status" name="registration_status" class="admin-input">
                                            <option value="pending">Pending Review</option>
                                            <option value="approved">Approved & ACCREDITED</option>
                                            <option value="rejected">Rejected Application</option>
                                            <option value="waiting_list">Waiting List</option>
                                        </select>
                                    </div>

                                    <div class="admin-form-group">
                                        <label for="v-remarks" class="fw-semibold mb-1">Rejection Remarks / Notes (Optional)</label>
                                        <textarea id="v-remarks" name="rejection_remarks" class="admin-input" rows="3" placeholder="e.g. Bank receipt blurry or reference mismatch..."></textarea>
                                    </div>

                                    <button type="submit" class="admin-btn admin-btn-primary" style="width:100%; font-weight:700; padding:0.7rem 0;">Update Participant</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                function openVerificationModal(reg) {
                    const modal = document.getElementById('verify-modal');
                    
                    // Set inputs
                    document.getElementById('v-id-input').value = reg.id;
                    document.getElementById('v-name').textContent = reg.snapshot_name;
                    document.getElementById('v-reg-no').textContent = reg.snapshot_regn_no;
                    document.getElementById('v-email').textContent = reg.snapshot_email;
                    document.getElementById('v-mobile').textContent = reg.snapshot_mobile;
                    document.getElementById('v-dob').textContent = reg.snapshot_dob;
                    document.getElementById('v-gender').textContent = reg.snapshot_gender;
                    document.getElementById('v-state').textContent = reg.snapshot_state;
                    document.getElementById('v-class').textContent = reg.snapshot_classification || 'N/A';
                    document.getElementById('v-tx-ref').textContent = reg.transaction_reference || 'N/A';
                    document.getElementById('v-payment-status').value = reg.payment_status;
                    document.getElementById('v-reg-status').value = reg.registration_status;
                    document.getElementById('v-remarks').value = reg.rejection_remarks || '';

                    // Fetch custom responses via fetch API
                    const answersDiv = document.getElementById('v-custom-answers');
                    answersDiv.innerHTML = '<span class="text-muted">Loading custom responses...</span>';
                    
                    fetch('../api/get-event-answers.php?reg_id=' + reg.id)
                        .then(response => response.json())
                        .then(data => {
                            answersDiv.innerHTML = '';
                            if(data.status === 'success' && data.answers.length > 0) {
                                data.answers.forEach(ans => {
                                    const div = document.createElement('div');
                                    div.style.marginBottom = '0.5rem';
                                    
                                    let valContent = ans.value;
                                    if(ans.type === 'file' || ans.type === 'image') {
                                        valContent = `<a href="../${ans.value}" target="_blank" class="fw-semibold text-primary"><i class="fa-solid fa-file-arrow-down"></i> Download File Attachment</a>`;
                                    }
                                    
                                    div.innerHTML = `<strong>${ans.label}:</strong> ${valContent}`;
                                    answersDiv.appendChild(div);
                                });
                            } else {
                                answersDiv.innerHTML = '<span class="text-muted">No custom form answers submitted.</span>';
                            }
                        })
                        .catch(err => {
                            answersDiv.innerHTML = '<span class="text-danger">Failed to load custom responses.</span>';
                        });

                    // Set Payment proof receipt file link
                    const receiptDiv = document.getElementById('v-receipt-container');
                    receiptDiv.innerHTML = '';
                    if (reg.payment_receipt_path) {
                        const link = document.createElement('a');
                        link.href = '../' + reg.payment_receipt_path;
                        link.target = '_blank';
                        link.className = 'admin-btn admin-btn-outline d-inline-flex align-items-center gap-2';
                        link.innerHTML = '<i class="fa-solid fa-file-pdf text-danger"></i> View Payment Receipt';
                        receiptDiv.appendChild(link);
                        
                        // If image format, display thumbnail preview
                        const ext = reg.payment_receipt_path.split('.').pop().toLowerCase();
                        if (['jpg', 'jpeg', 'png'].includes(ext)) {
                            const img = document.createElement('img');
                            img.src = '../' + reg.payment_receipt_path;
                            img.alt = 'Receipt Preview';
                            img.style.maxWidth = '100%';
                            img.style.maxHeight = '200px';
                            img.style.marginTop = '1rem';
                            img.style.borderRadius = '8px';
                            img.style.border = '1px solid #cbd5e1';
                            receiptDiv.appendChild(img);
                        }
                    } else {
                        receiptDiv.innerHTML = '<span class="text-muted">No payment receipt file uploaded.</span>';
                    }

                    modal.style.display = 'flex';
                }

                function closeVerificationModal() {
                    document.getElementById('verify-modal').style.display = 'none';
                }
                </script>
            <?php endif; ?>

        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
