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

<div class="admin-wrapper" style="background:#08142E; min-height:95vh; padding:6rem 0; color:#FAF7F0;">
    <div class="container">
        
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:3rem; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:1.5rem;">
            <div>
                <span style="color:#24C27A; text-transform:uppercase; letter-spacing:0.05em; font-weight:600; font-size:0.9rem;">National Calendar</span>
                <h1 style="font-family:'Outfit',sans-serif; font-size:2.5rem; font-weight:700;">Coordinate Events & Championships</h1>
            </div>
            <div style="display:flex; gap:0.75rem;">
                <button onclick="openEventModal(0)" class="btn" style="background:#24C27A; color:#08142E; font-weight:bold; border-radius:999px; cursor:pointer;">Add Calendar Event</button>
                <a href="dashboard.php" class="btn" style="border:1px solid rgba(255,255,255,0.15); color:#FAF7F0; border-radius:999px;">Return to Dashboard</a>
            </div>
        </div>

        <?php echo $message; ?>

        <!-- Events List -->
        <div style="display:flex; flex-direction:column; gap:1.5rem;">
            <?php if (count($eventsList) > 0): ?>
                <?php foreach ($eventsList as $item): ?>
                    <div class="glass-card" style="background:rgba(22, 41, 90, 0.4); padding:2rem; border-radius:28px; display:grid; grid-template-columns:3fr 1fr; gap:2rem; align-items:center;">
                        <div>
                            <div style="display:flex; align-items:center; gap:1rem; margin-bottom:0.75rem;">
                                <h3 style="font-size:1.4rem; font-family:'Outfit',sans-serif;"><?php echo htmlspecialchars($item['title']); ?></h3>
                                <span style="font-size:0.8rem; background:rgba(255,255,255,0.05); border:1px solid #1E88E5; color:#1E88E5; padding:0.2rem 0.5rem; border-radius:4px; text-transform:uppercase; font-weight:600;"><?php echo htmlspecialchars($item['status']); ?></span>
                            </div>
                            <p style="font-size:0.95rem; opacity:0.9; margin-bottom:0.5rem;"><strong>📍 Location:</strong> <?php echo htmlspecialchars($item['location']); ?></p>
                            <p style="font-size:0.95rem; opacity:0.9; margin-bottom:0.5rem;"><strong>🗓️ Dates:</strong> <?php echo htmlspecialchars($item['start_date']) . ' to ' . htmlspecialchars($item['end_date']); ?></p>
                            <p style="font-size:0.9rem; opacity:0.7; margin-top:0.5rem;"><?php echo htmlspecialchars($item['description'] ?: 'No description provided.'); ?></p>
                        </div>
                        <div style="display:flex; flex-direction:column; gap:0.5rem; justify-content:center;">
                            <button onclick="openEventModal(<?php echo htmlspecialchars(json_encode($item)); ?>)" class="btn" style="border:1px solid #24C27A; color:#24C27A; padding:0.6rem; font-weight:bold; border-radius:999px; cursor:pointer; text-align:center;">Edit Event</button>
                            <form action="events.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this event listing?');" style="display:block;">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="event_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" name="delete_event" class="btn" style="border:1px solid #D72638; color:#D72638; padding:0.6rem; font-weight:bold; border-radius:999px; cursor:pointer; width:100%; text-align:center;">Delete Listing</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="glass-card" style="background:rgba(22, 41, 90, 0.2); padding:4rem; border-radius:28px; text-align:center;">
                    <p style="font-size:1.1rem; opacity:0.7;">No calendar events scheduled. Click "Add Calendar Event" to create one.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- Modal Form Editor for Events -->
<div id="event-modal" class="lightbox" style="display:none; align-items:center; justify-content:center; background:rgba(0,0,0,0.8);">
    <div class="glass-card" style="background:#08142E; border:1px solid rgba(255,255,255,0.1); padding:2.5rem; border-radius:28px; max-width:550px; width:90%; position:relative;">
        <button onclick="closeEventModal()" style="position:absolute; top:15px; right:15px; background:none; border:none; color:#FAF7F0; font-size:1.5rem; cursor:pointer;">&times;</button>
        <h3 id="modal-title" style="font-family:'Outfit',sans-serif; font-size:1.6rem; margin-bottom:1.5rem;">Add Calendar Event</h3>
        
        <form id="event-editor-form" style="display:flex; flex-direction:column; gap:1.25rem;">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="id" id="event-id" value="0">
            
            <div class="form-group">
                <label for="event-title" style="font-size:0.8rem; font-weight:600;">Event Title / Name</label>
                <input type="text" id="event-title" name="title" class="form-input" required placeholder="e.g. 11th Senior Nationals">
            </div>
            
            <div class="form-group">
                <label for="event-location" style="font-size:0.8rem; font-weight:600;">Location / Venue</label>
                <input type="text" id="event-location" name="location" class="form-input" required placeholder="e.g. Margao, Goa">
            </div>
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                <div class="form-group">
                    <label for="event-start" style="font-size:0.8rem; font-weight:600;">Start Date</label>
                    <input type="date" id="event-start" name="start_date" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="event-end" style="font-size:0.8rem; font-weight:600;">End Date</label>
                    <input type="date" id="event-end" name="end_date" class="form-input" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="event-desc" style="font-size:0.8rem; font-weight:600;">Short Description</label>
                <textarea id="event-desc" name="description" class="form-input" placeholder="Briefly describe key details, trial selection options..." style="min-height:80px;"></textarea>
            </div>
            
            <div class="form-group">
                <label for="event-status" style="font-size:0.8rem; font-weight:600;">Status</label>
                <select id="event-status" name="status" class="select-input">
                    <option value="upcoming">Upcoming</option>
                    <option value="ongoing">Ongoing</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            <button type="submit" class="btn" style="background:#24C27A; color:#08142E; font-weight:bold; padding:0.85rem; border-radius:999px; cursor:pointer; margin-top:0.5rem;">Save Event</button>
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
