<?php
// get-involved/verify-membership.php - Membership Verification Portal
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$search = isset($_POST['regn_no']) ? trim($_POST['regn_no']) : '';
$result = null;
$error = '';
$searched = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $searched = true;
    if (empty($search)) {
        $error = "Please enter a Registration Number or Official ID.";
    } else {
        try {
            // First check if it is an Official ID starting with 'OF-' (case-insensitive) or look in officials table
            if (stripos($search, 'OF-') === 0 || preg_match('/^[a-zA-Z]/', $search)) {
                $stmt = $pdo->prepare("SELECT * FROM officials WHERE (official_reg_no = ? OR id = ?) AND status = 'approved' AND deleted_at IS NULL");
                $stmt->execute([$search, $search]);
                $off = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($off) {
                    $result = [
                        'type' => 'Official',
                        'reg_no' => $off['official_reg_no'],
                        'name' => $off['name'],
                        'state' => $off['state'],
                        'role_class' => $off['role'],
                        'status' => 'Active / Approved',
                        'gender' => $off['gender'],
                        'photo' => $off['photo_path']
                    ];
                }
            }
            
            // If not found in officials, look in athletes table
            if (!$result) {
                // Pad numerical strings to 4 digits to match standard e.g. "0003"
                $lookupReg = $search;
                if (is_numeric($lookupReg)) {
                    $lookupReg = str_pad($lookupReg, 4, '0', STR_PAD_LEFT);
                }
                
                $stmt = $pdo->prepare("SELECT * FROM athletes WHERE (regn_no = ? OR id = ?) AND status = 'approved' AND deleted_at IS NULL");
                $stmt->execute([$lookupReg, $lookupReg]);
                $ath = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($ath) {
                    $result = [
                        'type' => 'Athlete',
                        'reg_no' => $ath['regn_no'],
                        'name' => $ath['full_name'],
                        'state' => $ath['state'],
                        'role_class' => $ath['classification'],
                        'status' => 'Active / Approved',
                        'gender' => $ath['gender'],
                        'photo' => $ath['photo_path']
                    ];
                }
            }
            
            // Fallback: If search is numeric but not found with padding, try searching athletes for exact match without padding
            if (!$result && is_numeric($search)) {
                $stmt = $pdo->prepare("SELECT * FROM athletes WHERE regn_no = ? AND status = 'approved' AND deleted_at IS NULL");
                $stmt->execute([$search]);
                $ath = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($ath) {
                    $result = [
                        'type' => 'Athlete',
                        'reg_no' => $ath['regn_no'],
                        'name' => $ath['full_name'],
                        'state' => $ath['state'],
                        'role_class' => $ath['classification'],
                        'status' => 'Active / Approved',
                        'gender' => $ath['gender'],
                        'photo' => $ath['photo_path']
                    ];
                }
            }
            
            if (!$result) {
                $error = "No active approved membership found matching ID: " . htmlspecialchars($search);
            }
        } catch (PDOException $e) {
            $error = "Verification failed due to database query issue.";
        }
    }
}

$page_title = "Membership Verification - Boccia India";
$logo_path = "../";
include __DIR__ . '/../includes/header.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
:root {
    --boccia-navy: #081B4B;
    --boccia-green: #10B981;
    --boccia-saffron: #FF9933;
    --boccia-maroon: #8C201C;
    --font-heading-sub: 'Outfit', sans-serif;
    --font-body-custom: 'Plus Jakarta Sans', sans-serif;
}

.verification-bg {
    min-height: 80vh;
    background: linear-gradient(135deg, rgba(8, 27, 75, 0.95) 0%, rgba(140, 32, 28, 0.95) 100%), url('../about boccia/why boccia matter BG.png') no-repeat center center;
    background-size: cover;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 80px 0;
}

.glass-card-verification {
    background: rgba(255, 255, 255, 0.08);
    border-radius: 24px;
    padding: 50px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    width: 100%;
    max-width: 650px;
    color: #ffffff;
}

.verification-input-group {
    position: relative;
    display: flex;
    gap: 10px;
}

.verification-input {
    flex-grow: 1;
    background: rgba(255, 255, 255, 0.95);
    border: 2px solid transparent;
    border-radius: 12px;
    padding: 16px 20px;
    font-size: 1.1rem;
    font-family: var(--font-body-custom);
    color: var(--boccia-navy);
    font-weight: 600;
    transition: all 0.3s ease;
}

.verification-input:focus {
    outline: none;
    border-color: var(--boccia-saffron);
    box-shadow: 0 0 15px rgba(255, 153, 51, 0.4);
}

.btn-verify-submit {
    background: linear-gradient(135deg, var(--boccia-saffron) 0%, #E68015 100%);
    border: none;
    border-radius: 12px;
    padding: 0 30px;
    color: #ffffff;
    font-family: var(--font-heading-sub);
    font-weight: 700;
    font-size: 1.1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    display: flex;
    align-items: center;
    gap: 10px;
}

.btn-verify-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(230, 128, 21, 0.4);
}

.member-badge {
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.25);
    padding: 6px 14px;
    border-radius: 50px;
    font-family: var(--font-heading-sub);
    font-weight: 700;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    display: inline-block;
    margin-bottom: 20px;
}

.badge-athlete {
    border-color: #3B82F6;
    color: #93C5FD;
}

.badge-official {
    border-color: #10B981;
    color: #A7F3D0;
}

.certificate-card {
    background: rgba(255, 255, 255, 0.98);
    border-radius: 20px;
    padding: 35px;
    margin-top: 40px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
    color: var(--boccia-navy);
    position: relative;
    overflow: hidden;
    animation: fadeInUp 0.6s cubic-bezier(0.165, 0.84, 0.44, 1) forwards;
}

.certificate-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 6px;
    height: 100%;
}

.certificate-card.cert-athlete::before {
    background: var(--boccia-maroon);
}

.certificate-card.cert-official::before {
    background: var(--boccia-navy);
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<div class="verification-bg">
    <div class="container d-flex justify-content-center">
        <div class="glass-card-verification text-center">
            
            <div class="mb-4">
                <img src="../boccia-india-logo.webp" alt="BSFI Logo" style="max-height: 80px;" class="mb-3">
                <h1 style="font-family: var(--font-heading-sub); font-weight: 800; text-transform: uppercase; letter-spacing: -0.01em;">Verify Membership</h1>
                <p style="font-family: var(--font-body-custom); color: rgba(255,255,255,0.8); font-size: 0.95rem;">
                    Instantly verify active registrations for players, coaches, technical officials, classifiers, and referees.
                </p>
            </div>

            <form action="verify-membership.php" method="POST" class="mt-4">
                <div class="verification-input-group">
                    <input type="text" name="regn_no" value="<?php echo htmlspecialchars($search); ?>" class="verification-input" placeholder="Enter REGN_NO or Official ID (e.g. 0003 or OF-0001)">
                    <button type="submit" class="btn-verify-submit">
                        <i class="bi bi-shield-fill-check"></i> Verify
                    </button>
                </div>
            </form>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger border-0 p-3 mt-4 rounded-3 text-start" style="background-color: rgba(239, 68, 68, 0.15); color: #FCA5A5; border: 1px solid rgba(239, 68, 68, 0.3) !important;">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($result): ?>
                <div class="certificate-card text-start cert-<?php echo strtolower($result['type']); ?>">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <span class="member-badge badge-<?php echo strtolower($result['type']); ?>">
                                <?php echo $result['type']; ?> Membership
                            </span>
                            <h2 style="font-family: var(--font-heading-sub); font-weight: 800; margin: 0; color: var(--boccia-navy);"><?php echo htmlspecialchars($result['name']); ?></h2>
                            <p class="text-muted mb-0" style="font-size: 0.95rem;">ID: <code><?php echo htmlspecialchars($result['reg_no']); ?></code></p>
                        </div>
                        <div class="bg-success text-white px-3 py-1 rounded-pill" style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">
                            <i class="bi bi-patch-check-fill me-1"></i> Active
                        </div>
                    </div>

                    <div class="row g-3" style="font-family: var(--font-body-custom); font-size: 0.95rem;">
                        <div class="col-6">
                            <span class="text-muted d-block" style="font-size: 0.8rem; text-transform: uppercase;">State / Territory</span>
                            <strong style="color: var(--boccia-navy);"><?php echo htmlspecialchars($result['state']); ?></strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block" style="font-size: 0.8rem; text-transform: uppercase;"><?php echo $result['type'] === 'Athlete' ? 'Classification' : 'Role'; ?></span>
                            <strong style="color: var(--boccia-navy);"><?php echo htmlspecialchars($result['role_class']); ?></strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block" style="font-size: 0.8rem; text-transform: uppercase;">Gender</span>
                            <strong style="color: var(--boccia-navy);"><?php echo htmlspecialchars($result['gender']); ?></strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block" style="font-size: 0.8rem; text-transform: uppercase;">Verified On</span>
                            <strong style="color: var(--boccia-navy);"><?php echo date('d M Y'); ?></strong>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                        <?php if (!empty($result['photo'])): ?>
                            <div class="d-flex align-items-center gap-3">
                                <img src="../<?php echo htmlspecialchars($result['photo']); ?>" alt="Profile Photo" style="width: 60px; height: 60px; object-fit: cover; border-radius: 50%; border: 2px solid #CBD5E1;">
                                <div>
                                    <span class="d-block text-muted" style="font-size: 0.75rem;">Verified Digital Profile</span>
                                    <span class="badge bg-secondary" style="font-size: 0.75rem;">Boccia India Registered</span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="d-flex align-items-center gap-3">
                                <div style="width: 60px; height: 60px; border-radius: 50%; background: #E2E8F0; display: flex; align-items: center; justify-content: center; border: 2px solid #CBD5E1;">
                                    <i class="bi bi-person-fill" style="font-size: 1.8rem; color: #94A3B8;"></i>
                                </div>
                                <div>
                                    <span class="d-block text-muted" style="font-size: 0.75rem;">No Photo Uploaded</span>
                                    <span class="badge bg-warning text-dark" style="font-size: 0.75rem;">Missing Photo</span>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <a href="update-profile.php?type=<?php echo strtolower($result['type']); ?>&id=<?php echo urlencode($result['reg_no']); ?>" class="btn btn-sm btn-primary rounded-pill px-3">
                            <i class="bi bi-pencil-square me-1"></i> Update My Profile
                        </a>
                    </div>
                </div>
            <?php elseif ($searched && empty($error)): ?>
                <div class="alert alert-warning border-0 p-3 mt-4 rounded-3 text-start" style="background-color: rgba(245, 158, 11, 0.15); color: #FDE68A; border: 1px solid rgba(245, 158, 11, 0.3) !important;">
                    <i class="bi bi-shield-fill-exclamation me-2"></i> Verification failed. Try again with a valid active registration ID.
                </div>
            <?php endif; ?>

            <div class="mt-5">
                <a href="membership.php" class="text-white text-decoration-none" style="font-family: var(--font-heading-sub); font-weight: 600; font-size: 0.95rem;">
                    <i class="bi bi-arrow-left me-1"></i> Back to Membership Portal
                </a>
            </div>

        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
