<?php
// schedules.php - Admin panel to manage National Calendar / Schedules

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role-check.php';

// Restricted to admin & editor
requireLogin();
if (!in_array($_SESSION['role'], ['admin', 'editor'])) {
    checkRole(['admin', 'editor']);
}

$page_title = "Manage Schedules - BSFI Admin";
include __DIR__ . '/../includes/header.php';

$message = '';

// Handle Delete
if (isset($_POST['delete_schedule']) && isset($_POST['schedule_id'])) {
    if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
         $message = "<div class='alert alert-danger'>Invalid CSRF Token.</div>";
    } else {
         $scheduleId = (int)$_POST['schedule_id'];
         $stmt = $pdo->prepare("DELETE FROM schedules WHERE id=?");
         $stmt->execute([$scheduleId]);
         logAction($pdo, "Deleted Schedule", "schedules", $scheduleId);
         $message = "<div class='alert alert-success'>Schedule deleted successfully.</div>";
    }
}

// Handle Save (Create/Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_schedule'])) {
    if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
         $message = "<div class='alert alert-danger'>Invalid CSRF Token.</div>";
    } else {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $discipline = trim($_POST['discipline']);
        $event_type = trim($_POST['event_type']);
        $date_text = trim($_POST['date_text']);
        $venue = trim($_POST['venue']);
        $registration_link = trim($_POST['registration_link']);
        $sort_order = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
        $active = isset($_POST['active']) ? 1 : 0;

        if (empty($discipline) || empty($date_text) || empty($venue)) {
            $message = "<div class='alert alert-danger'>Discipline, Date, and Venue are required.</div>";
        } else {
            if ($id > 0) {
                // Update
                $stmt = $pdo->prepare("UPDATE schedules SET discipline=?, event_type=?, date_text=?, venue=?, registration_link=?, sort_order=?, active=? WHERE id=?");
                $stmt->execute([$discipline, $event_type, $date_text, $venue, $registration_link, $sort_order, $active, $id]);
                logAction($pdo, "Updated Schedule", "schedules", $id);
                $message = "<div class='alert alert-success'>Schedule updated successfully.</div>";
            } else {
                // Insert
                $stmt = $pdo->prepare("INSERT INTO schedules (discipline, event_type, date_text, venue, registration_link, sort_order, active) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$discipline, $event_type, $date_text, $venue, $registration_link, $sort_order, $active]);
                $newId = $pdo->lastInsertId();
                logAction($pdo, "Added Schedule", "schedules", $newId);
                $message = "<div class='alert alert-success'>Schedule added successfully.</div>";
            }
        }
    }
}

// Fetch schedules
$stmt = $pdo->query("SELECT * FROM schedules ORDER BY sort_order ASC, id DESC");
$schedulesList = $stmt->fetchAll();
?>

<div class="admin-wrapper">
    <div class="container-fluid" style="padding: 2rem;">
        
        <div class="admin-page-title-row">
            <div>
                <span class="admin-section-eyebrow">National Calendar</span>
                <h1 class="admin-page-title">Manage Schedules</h1>
            </div>
            <div style="display:flex; gap:0.75rem;">
                <button onclick="openScheduleModal(0)" class="admin-btn admin-btn-primary">Add Schedule</button>
                <a href="dashboard.php" class="admin-btn admin-btn-outline">Return to Dashboard</a>
            </div>
        </div>

        <?php echo $message; ?>

        <!-- Schedules List -->
        <div style="display:flex; flex-direction:column; gap:1.5rem;">
            <?php if (count($schedulesList) > 0): ?>
                <?php foreach ($schedulesList as $item): ?>
                    <div class="admin-card" style="display:grid; grid-template-columns:3fr 1fr; gap:2rem; align-items:center; margin-bottom: 0; <?php echo !$item['active'] ? 'opacity: 0.6;' : ''; ?>">
                        <div>
                            <div style="display:flex; align-items:center; gap:1rem; margin-bottom:0.75rem;">
                                <h3 class="admin-card-title" style="font-size:1.4rem; margin:0;"><?php echo htmlspecialchars($item['discipline']); ?></h3>
                                <?php if($item['event_type']): ?>
                                    <span class="admin-badge admin-badge-info"><?php echo htmlspecialchars($item['event_type']); ?></span>
                                <?php endif; ?>
                                <?php if(!$item['active']): ?>
                                    <span class="admin-badge admin-badge-danger">Inactive</span>
                                <?php endif; ?>
                            </div>
                            <p style="font-size:0.95rem; color:var(--text-secondary); margin-bottom:0.5rem;"><strong>🗓️ Date:</strong> <?php echo htmlspecialchars($item['date_text']); ?></p>
                            <p style="font-size:0.95rem; color:var(--text-secondary); margin-bottom:0.5rem;"><strong>📍 Venue:</strong> <?php echo htmlspecialchars($item['venue']); ?></p>
                            <div style="font-size:0.9rem; color:var(--text-muted); margin-top:0.5rem; display:flex; gap:1.5rem;">
                                <span><strong>Sort Order:</strong> <?php echo (int)$item['sort_order']; ?></span>
                                <?php if($item['registration_link']): ?>
                                    <span><strong>🔗 URL:</strong> <?php echo htmlspecialchars($item['registration_link']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="display:flex; flex-direction:column; gap:0.5rem; justify-content:center;">
                            <button onclick="openScheduleModal(<?php echo htmlspecialchars(json_encode($item)); ?>)" class="admin-btn admin-btn-primary">Edit Schedule</button>
                            <form action="schedules.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this schedule?');" style="display:block; margin:0;">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="schedule_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" name="delete_schedule" class="admin-btn admin-btn-danger" style="width:100%;">Delete Schedule</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="admin-card" style="text-align:center; padding:4rem;">
                    <p style="font-size:1.15rem; color:var(--text-secondary); margin:0;">No schedules found. Click "Add Schedule" to create one.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- Modal Form Editor for Schedules -->
<div id="schedule-modal" class="lightbox" style="display:none; align-items:center; justify-content:center; background:rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 1000;">
    <div class="admin-card" style="background:#FFFFFF; border:1px solid #E2E8F0; padding:2.5rem; border-radius:20px; max-width:600px; width:90%; position:relative; max-height: 90vh; overflow-y: auto; color: var(--text-primary); margin-bottom:0; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
        <button onclick="closeScheduleModal()" style="position:absolute; top:15px; right:15px; background:none; border:none; color:var(--text-muted); font-size:1.5rem; cursor:pointer;">&times;</button>
        <h3 id="modal-title" style="font-family:'Outfit',sans-serif; font-size:1.6rem; margin-bottom:1.5rem; color: var(--navy); font-weight: 700;">Add Schedule</h3>
        
        <form action="schedules.php" method="POST" id="schedule-editor-form" style="display:flex; flex-direction:column; gap:1.25rem;">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="save_schedule" value="1">
            <input type="hidden" name="id" id="schedule-id" value="0">
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                <div class="admin-form-group">
                    <label for="schedule-discipline">Discipline <span style="color:var(--danger)">*</span></label>
                    <input type="text" id="schedule-discipline" name="discipline" class="admin-input" required placeholder="e.g. Para Archery">
                </div>
                <div class="admin-form-group">
                    <label for="schedule-type">Event Type (Optional)</label>
                    <input type="text" id="schedule-type" name="event_type" class="admin-input" placeholder="e.g. National Championship">
                </div>
            </div>

            <div class="admin-form-group">
                <label for="schedule-date">Date Display Text <span style="color:var(--danger)">*</span></label>
                <input type="text" id="schedule-date" name="date_text" class="admin-input" required placeholder="e.g. 22-23 March, 2026">
            </div>
            
            <div class="admin-form-group">
                <label for="schedule-venue">Venue <span style="color:var(--danger)">*</span></label>
                <input type="text" id="schedule-venue" name="venue" class="admin-input" required placeholder="e.g. JLN Stadium, New Delhi">
            </div>
            
            <div class="admin-form-group">
                <label for="schedule-link">Registration URL (Optional)</label>
                <input type="url" id="schedule-link" name="registration_link" class="admin-input" placeholder="https://...">
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; align-items:center;">
                <div class="admin-form-group">
                    <label for="schedule-sort">Sort Order</label>
                    <input type="number" id="schedule-sort" name="sort_order" class="admin-input" value="0" placeholder="1 = First, 2 = Second...">
                </div>
                <div class="admin-form-group" style="display:flex; align-items:center; gap:0.5rem; margin-top: 1.5rem;">
                    <input type="checkbox" id="schedule-active" name="active" value="1" checked style="width: 18px; height: 18px; cursor:pointer;">
                    <label for="schedule-active" style="font-size:0.9rem; font-weight:600; cursor:pointer; margin-bottom:0;">Active / Visible</label>
                </div>
            </div>
            
            <button type="submit" class="admin-btn admin-btn-primary" style="width:100%; margin-top:0.5rem;">Save Schedule</button>
        </form>
    </div>
</div>

<script>
function openScheduleModal(item) {
    const modal = document.getElementById('schedule-modal');
    const form = document.getElementById('schedule-editor-form');
    
    if (item === 0) {
        document.getElementById('modal-title').textContent = "Add Schedule";
        document.getElementById('schedule-id').value = 0;
        form.reset();
        document.getElementById('schedule-active').checked = true;
    } else {
        document.getElementById('modal-title').textContent = "Edit Schedule";
        document.getElementById('schedule-id').value = item.id;
        document.getElementById('schedule-discipline').value = item.discipline;
        document.getElementById('schedule-type').value = item.event_type || '';
        document.getElementById('schedule-date').value = item.date_text;
        document.getElementById('schedule-venue').value = item.venue;
        document.getElementById('schedule-link').value = item.registration_link || '';
        document.getElementById('schedule-sort').value = item.sort_order;
        document.getElementById('schedule-active').checked = item.active == 1;
    }
    
    modal.style.display = 'flex';
}

function closeScheduleModal() {
    document.getElementById('schedule-modal').style.display = 'none';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
