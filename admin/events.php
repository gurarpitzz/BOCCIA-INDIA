<?php
// events.php - Admin / Editor championship coordinator panel

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role-check.php';

// Restricted to admin & editor
requireLogin();
if (!in_array($_SESSION['role'], ['admin', 'editor'])) {
    checkRole(['admin', 'editor']);
}

$page_title = "Coordinate Calendar - BSFI Admin";
include __DIR__ . '/../includes/header.php';

$message = '';

if (isset($_POST['delete_event']) && isset($_POST['event_id'])) {
    if (!isset($_POST['csrf_token']) || !validateCSRF($_POST['csrf_token'])) {
         $message = "<div class='alert alert-danger'>Invalid CSRF Token.</div>";
    } else {
         $eventId = (int)$_POST['event_id'];
         $stmt = $pdo->prepare("DELETE FROM events WHERE id=?");
         $stmt->execute([$eventId]);
         logAction($pdo, "Deleted Event Listing", "events", $eventId);
         $message = "<div class='alert alert-success'>Event listing deleted successfully.</div>";
    }
}

// Fetch events
$stmt = $pdo->query("SELECT * FROM events ORDER BY start_date DESC");
$eventsList = $stmt->fetchAll();
?>

<div class="admin-wrapper">
    <div class="container-fluid" style="padding: 2rem;">
        
        <div class="admin-page-title-row">
            <div>
                <span class="admin-section-eyebrow">National Calendar</span>
                <h1 class="admin-page-title">Coordinate Events & Championships</h1>
            </div>
            <div style="display:flex; gap:0.75rem;">
                <button onclick="openEventModal(0)" class="admin-btn admin-btn-primary">Add Calendar Event</button>
                <a href="dashboard.php" class="admin-btn admin-btn-outline">Return to Dashboard</a>
            </div>
        </div>

        <?php echo $message; ?>

        <!-- Events List -->
        <div style="display:flex; flex-direction:column; gap:1.5rem;">
            <?php if (count($eventsList) > 0): ?>
                <?php foreach ($eventsList as $item): ?>
                    <div class="admin-card" style="display:grid; grid-template-columns:3fr 1fr; gap:2rem; align-items:center; margin-bottom: 0;">
                        <div>
                            <div style="display:flex; align-items:center; gap:1rem; margin-bottom:0.75rem;">
                                <h3 class="admin-card-title" style="font-size:1.4rem; margin:0;"><?php echo htmlspecialchars($item['title']); ?></h3>
                                <span class="admin-badge <?php 
                                    if ($item['status'] === 'upcoming') echo 'admin-badge-pending';
                                    elseif ($item['status'] === 'ongoing') echo 'admin-badge-warning';
                                    elseif ($item['status'] === 'completed') echo 'admin-badge-success';
                                    else echo 'admin-badge-danger';
                                ?>"><?php echo htmlspecialchars($item['status']); ?></span>
                            </div>
                            <p style="font-size:0.95rem; color:var(--text-secondary); margin-bottom:0.5rem;"><strong>Location:</strong> <?php echo htmlspecialchars($item['location']); ?></p>
                            <p style="font-size:0.95rem; color:var(--text-secondary); margin-bottom:0.5rem;"><strong>Dates:</strong> <?php echo htmlspecialchars($item['start_date']) . ' to ' . htmlspecialchars($item['end_date']); ?></p>
                            <p style="font-size:0.9rem; color:var(--text-muted); margin-top:0.5rem;"><?php echo htmlspecialchars($item['description'] ?: 'No description provided.'); ?></p>
                        </div>
                        <div style="display:flex; flex-direction:column; gap:0.5rem; justify-content:center;">
                            <button onclick="openEventModal(<?php echo htmlspecialchars(json_encode($item)); ?>)" class="admin-btn admin-btn-primary">Edit Event</button>
                            <form action="events.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this event listing?');" style="display:block; margin:0;">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="event_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" name="delete_event" class="admin-btn admin-btn-danger" style="width:100%;">Delete Listing</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="admin-card" style="text-align:center; padding:4rem;">
                    <p style="font-size:1.15rem; color:var(--text-secondary); margin:0;">No calendar events scheduled. Click "Add Calendar Event" to create one.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- Modal Form Editor for Events -->
<div id="event-modal" class="lightbox" style="display:none; align-items:center; justify-content:center; background:rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 1000;">
    <div class="admin-card" style="background:#FFFFFF; border:1px solid #E2E8F0; padding:2.5rem; border-radius:20px; max-width:550px; width:90%; position:relative; color: var(--text-primary); margin-bottom:0; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
        <button onclick="closeEventModal()" style="position:absolute; top:15px; right:15px; background:none; border:none; color:var(--text-muted); font-size:1.5rem; cursor:pointer;">&times;</button>
        <h3 id="modal-title" style="font-family:'Outfit',sans-serif; font-size:1.6rem; margin-bottom:1.5rem; color: var(--navy); font-weight:700;">Add Calendar Event</h3>
        
        <form id="event-editor-form" style="display:flex; flex-direction:column; gap:1.25rem;">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="id" id="event-id" value="0">
            
            <div class="admin-form-group">
                <label for="event-title">Event Title / Name</label>
                <input type="text" id="event-title" name="title" class="admin-input" required placeholder="e.g. 11th Senior Nationals">
            </div>
            
            <div class="admin-form-group">
                <label for="event-location">Location / Venue</label>
                <input type="text" id="event-location" name="location" class="admin-input" required placeholder="e.g. Margao, Goa">
            </div>
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                <div class="admin-form-group">
                    <label for="event-start">Start Date</label>
                    <input type="date" id="event-start" name="start_date" class="admin-input" required>
                </div>
                <div class="admin-form-group">
                    <label for="event-end">End Date</label>
                    <input type="date" id="event-end" name="end_date" class="admin-input" required>
                </div>
            </div>
            
            <div class="admin-form-group">
                <label for="event-desc">Short Description</label>
                <textarea id="event-desc" name="description" class="admin-textarea" placeholder="Briefly describe key details, trial selection options..." style="min-height:80px;"></textarea>
            </div>
            
            <div class="admin-form-group">
                <label for="event-status">Status</label>
                <select id="event-status" name="status" class="admin-select">
                    <option value="upcoming">Upcoming</option>
                    <option value="ongoing">Ongoing</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            <button type="submit" class="admin-btn admin-btn-primary" style="width:100%; margin-top:0.5rem;">Save Event</button>
        </form>
    </div>
</div>

<script>
function openEventModal(item) {
    const modal = document.getElementById('event-modal');
    const form = document.getElementById('event-editor-form');
    
    if (item === 0) {
        document.getElementById('modal-title').textContent = "Add Calendar Event";
        document.getElementById('event-id').value = 0;
        form.reset();
    } else {
        document.getElementById('modal-title').textContent = "Edit Calendar Event";
        document.getElementById('event-id').value = item.id;
        document.getElementById('event-title').value = item.title;
        document.getElementById('event-location').value = item.location;
        document.getElementById('event-start').value = item.start_date;
        document.getElementById('event-end').value = item.end_date;
        document.getElementById('event-desc').value = item.description;
        document.getElementById('event-status').value = item.status;
    }
    
    modal.style.display = 'flex';
}

function closeEventModal() {
    document.getElementById('event-modal').style.display = 'none';
}

document.getElementById('event-editor-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('../api/save-event.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.success);
            location.reload();
        } else {
            alert(data.error);
        }
    })
    .catch(err => alert("An error occurred."));
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
