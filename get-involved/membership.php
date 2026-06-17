<?php
// get-involved/membership.php - Dynamic player membership intake form

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$message = '';
$error = '';

// Load dynamic fields configuration
$fieldsJson = file_get_contents(__DIR__ . '/../includes/membership_fields.json');
$fields = json_decode($fieldsJson, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic csrf check could be added here
    $data = [];
    $files = [];
    $isValid = true;

    // Sanitize and validate inputs
    foreach ($fields as $field) {
        $name = $field['name'];
        if ($field['type'] === 'file') {
            if (empty($_FILES[$name]['name']) && $field['required']) {
                $error = "File upload for '{$field['label']}' is required.";
                $isValid = false;
                break;
            } elseif (!empty($_FILES[$name]['name'])) {
                // Perform file type and security validation
                $filename = $_FILES[$name]['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                $allowed = ['pdf', 'png', 'jpg', 'jpeg'];
                
                if (!in_array($ext, $allowed)) {
                    $error = "Invalid file type for '{$field['label']}'. Only PDF, PNG, JPG, and JPEG are allowed.";
                    $isValid = false;
                    break;
                }

                // Security mime type validation
                $tempFile = $_FILES[$name]['tmp_name'];
                $mime = mime_content_type($tempFile);
                $allowedMimes = ['application/pdf', 'image/png', 'image/jpeg', 'image/jpg'];
                if (!in_array($mime, $allowedMimes)) {
                    $error = "Invalid file content for '{$field['label']}'. Please upload genuine documents.";
                    $isValid = false;
                    break;
                }
                
                $files[$name] = $_FILES[$name];
            }
        } else {
            $val = trim($_POST[$name] ?? '');
            if ($field['required'] && empty($val)) {
                $error = "Field '{$field['label']}' is required.";
                $isValid = false;
                break;
            }
            $data[$name] = htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
        }
    }

    if ($isValid) {
        try {
            // Process file uploads
            $uploadedPaths = [];
            foreach ($files as $name => $fileInfo) {
                $ext = strtolower(pathinfo($fileInfo['name'], PATHINFO_EXTENSION));
                // Generate secure filename
                $secureName = uniqid('member_', true) . '.' . $ext;
                $destPath = __DIR__ . '/../uploads/memberships/' . $secureName;
                
                if (move_uploaded_file($fileInfo['tmp_name'], $destPath)) {
                    $uploadedPaths[$name] = 'uploads/memberships/' . $secureName;
                }
            }

            // Generate unique registration number
            $regnNo = 'BI-' . strtoupper(substr($data['state'], 0, 3)) . '-' . rand(10000, 99999);

            // Save details to database
            $ins = $pdo->prepare("INSERT INTO athletes (regn_no, full_name, gender, dob, mobile, email, state, district, classification, representing_for, wheelchair_status, photo_path, receipt_path, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            
            $ins->execute([
                $regnNo,
                $data['full_name'],
                strtoupper($data['gender']),
                $data['dob'],
                $data['phone'],
                $data['email'],
                $data['state'],
                $data['pincode'], // Storing pincode in district/other placeholder if needed
                $data['classification'],
                $data['state'],
                $data['wheelchair_status'],
                $uploadedPaths['photo_path'] ?? null,
                $uploadedPaths['receipt_path'] ?? null
            ]);

            $message = "Your membership application has been submitted successfully! Your tracking registration number is: <strong>$regnNo</strong>. It is currently under review.";
            
            // Log activity
            $log = $pdo->prepare("INSERT INTO activity_logs (action, details) VALUES ('Submit Membership', ?)");
            $log->execute(["New membership registration submitted: $regnNo"]);
        } catch (\PDOException $e) {
            $error = "Database error saving application: " . $e->getMessage();
        }
    }
}

$page_title = "Online Player Membership Registration - Boccia India";
$logo_path = "../";
include __DIR__ . '/../includes/header.php';
?>

<div class="container my-5 py-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            
            <div class="mb-5 border-bottom pb-3">
                <h1 class="display-5 text-dark fw-bold" style="color: #081B4B !important;">Online Membership Registration</h1>
                <p class="text-muted">Register as an athlete with the Boccia Sports Federation of India (BSFI).</p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-success border-0 p-4 mb-4 rounded-3">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger border-0 p-3 mb-4 rounded-3">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($message)): ?>
                <form action="membership.php" method="POST" enctype="multipart/form-data" class="bg-light p-4 rounded-4 shadow-sm border">
                    <div class="row">
                        <?php foreach ($fields as $field): 
                            $name = $field['name'];
                            $label = $field['label'];
                            $required = $field['required'] ? 'required' : '';
                        ?>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold text-dark"><?php echo htmlspecialchars($label); ?> <?php echo $field['required'] ? '<span class="text-danger">*</span>' : ''; ?></label>
                                
                                <?php if ($field['type'] === 'select'): ?>
                                    <select name="<?php echo $name; ?>" class="form-select" <?php echo $required; ?>>
                                        <option value="">Select option</option>
                                        <?php foreach ($field['options'] as $opt): ?>
                                            <option value="<?php echo htmlspecialchars($opt); ?>"><?php echo htmlspecialchars($opt); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php elseif ($field['type'] === 'textarea'): ?>
                                    <textarea name="<?php echo $name; ?>" class="form-control" rows="3" <?php echo $required; ?>></textarea>
                                <?php elseif ($field['type'] === 'file'): ?>
                                    <input type="file" name="<?php echo $name; ?>" class="form-control" <?php echo $required; ?>>
                                <?php else: ?>
                                    <input type="<?php echo $field['type']; ?>" name="<?php echo $name; ?>" class="form-control" <?php echo $required; ?>>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-primary btn-lg px-5">Submit Registration</button>
                    </div>
                </form>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
