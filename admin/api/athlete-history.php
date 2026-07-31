<?php
// admin/api/athlete-history.php - Unified Administrative Athlete Performance History AJAX Controller
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/constants.php';

// Verify authentication and role is strict admin
if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access. Administrator privileges required.']);
    exit();
}

// Validate CSRF token
$csrf = $_POST['csrf_token'] ?? '';
file_put_contents(__DIR__ . '/debug_post.txt', print_r($_POST, true));
if (empty($csrf) || $csrf !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['error' => 'Security validation token failed (CSRF).']);
    exit();
}

$action = isset($_POST['action']) ? trim($_POST['action']) : '';

try {
    if ($action === 'check_duplicate') {
        $athleteId = isset($_POST['athlete_id']) ? (int)$_POST['athlete_id'] : 0;
        $historyId = isset($_POST['history_id']) ? (int)$_POST['history_id'] : 0;
        $eventName = isset($_POST['event_name']) ? trim($_POST['event_name']) : '';
        $eventYear = isset($_POST['event_year']) ? (int)$_POST['event_year'] : 0;
        $classification = isset($_POST['classification']) ? trim($_POST['classification']) : '';
        $eventLevel = isset($_POST['event_level']) ? trim($_POST['event_level']) : '';

        if ($athleteId <= 0 || empty($eventName) || $eventYear <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields for duplicate check.']);
            exit();
        }

        $dupStmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM athlete_history 
            WHERE athlete_id = ? 
              AND LOWER(TRIM(event_name)) = LOWER(TRIM(?)) 
              AND event_year = ? 
              AND classification = ? 
              AND event_level = ? 
              AND id != ? 
              AND deleted_at IS NULL
        ");
        $dupStmt->execute([$athleteId, $eventName, $eventYear, $classification, $eventLevel, $historyId]);
        $count = (int)$dupStmt->fetchColumn();

        echo json_encode(['duplicate' => ($count > 0)]);
        exit();
    } 
    
    elseif ($action === 'save') {
        $historyId = isset($_POST['history_id']) ? (int)$_POST['history_id'] : 0;
        $athleteId = isset($_POST['athlete_id']) ? (int)$_POST['athlete_id'] : 0;
        $eventName = isset($_POST['event_name']) ? trim($_POST['event_name']) : '';
        $eventYear = isset($_POST['event_year']) ? (int)$_POST['event_year'] : 0;
        $classification = isset($_POST['classification']) ? trim($_POST['classification']) : '';
        $eventLevel = isset($_POST['event_level']) ? trim($_POST['event_level']) : '';
        $stateRepresented = isset($_POST['state_represented']) ? trim($_POST['state_represented']) : '';
        $rankSelect = isset($_POST['rank']) ? trim($_POST['rank']) : '';
        $customRank = isset($_POST['custom_rank']) ? trim($_POST['custom_rank']) : '';
        $remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : '';

        // Validation
        if ($athleteId <= 0 || empty($eventName) || $eventYear <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Athlete ID, Event Name, and Year are required fields.']);
            exit();
        }

        // Validate year range (no future years unless current year)
        $currentYear = (int)date('Y');
        if ($eventYear < 1900 || $eventYear > $currentYear) {
            http_response_code(400);
            echo json_encode(['error' => "Invalid Event Year. Year must be between 1900 and {$currentYear}."]);
            exit();
        }

        // Validate options against whitelisted constants
        if (!in_array($classification, CLASSIFICATIONS)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid Classification category selection.']);
            exit();
        }
        if (!in_array($eventLevel, EVENT_LEVELS)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid Event Level selection.']);
            exit();
        }
        if (!in_array($stateRepresented, INDIAN_STATES)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid State Represented selection.']);
            exit();
        }
        if (!in_array($rankSelect, RESULT_OPTIONS)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid Rank/Result selection.']);
            exit();
        }

        // Determine final rank/result value
        $finalRank = ($rankSelect === 'Other') ? $customRank : $rankSelect;
        if (empty($finalRank)) {
            http_response_code(400);
            echo json_encode(['error' => 'Rank/Result cannot be empty. Please specify a result.']);
            exit();
        }

        // Verify athlete exists
        $athStmt = $pdo->prepare("SELECT id, full_name, regn_no FROM athletes WHERE id = ? AND deleted_at IS NULL");
        $athStmt->execute([$athleteId]);
        $athlete = $athStmt->fetch();
        if (!$athlete) {
            http_response_code(404);
            echo json_encode(['error' => 'Target athlete profile not found.']);
            exit();
        }

        $pdo->beginTransaction();

        if ($historyId === 0) {
            // Create Operation
            $ins = $pdo->prepare("
                INSERT INTO athlete_history 
                (athlete_id, event_name, event_year, classification, event_level, state_represented, rank, remarks) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $ins->execute([$athleteId, $eventName, $eventYear, $classification, $eventLevel, $stateRepresented, $finalRank, $remarks]);
            $newId = $pdo->lastInsertId();

            // Log Detailed Audit
            $logDetails = "Added Tournament Record for " . $athlete['full_name'] . " (Reg No: " . $athlete['regn_no'] . ") | Event: {$eventName} | Year: {$eventYear} | Level: {$eventLevel} | Class: {$classification} | Result: {$finalRank} | Remarks: {$remarks}";
            logAction($pdo, 'athlete_history_created', 'athletes', $athleteId, $logDetails);

            $pdo->commit();
            echo json_encode(['success' => 'Tournament performance record added successfully.', 'id' => $newId]);
            exit();
        } else {
            // Update Operation
            // Fetch old record for change-tracking log
            $oldStmt = $pdo->prepare("SELECT * FROM athlete_history WHERE id = ? AND athlete_id = ? AND deleted_at IS NULL");
            $oldStmt->execute([$historyId, $athleteId]);
            $oldRecord = $oldStmt->fetch();
            if (!$oldRecord) {
                http_response_code(404);
                echo json_encode(['error' => 'History record not found or has been deleted.']);
                exit();
            }

            $upd = $pdo->prepare("
                UPDATE athlete_history 
                SET event_name = ?, event_year = ?, classification = ?, event_level = ?, state_represented = ?, rank = ?, remarks = ? 
                WHERE id = ? AND athlete_id = ?
            ");
            $upd->execute([$eventName, $eventYear, $classification, $eventLevel, $stateRepresented, $finalRank, $remarks, $historyId, $athleteId]);

            // Compare fields to log old vs new
            $changes = [];
            $fieldsToCompare = [
                'event_name' => 'Event Name',
                'event_year' => 'Event Year',
                'classification' => 'Classification',
                'event_level' => 'Event Level',
                'state_represented' => 'State Represented',
                'rank' => 'Rank/Result',
                'remarks' => 'Remarks'
            ];
            foreach ($fieldsToCompare as $col => $lbl) {
                $oldVal = $oldRecord[$col] ?? '';
                $newVal = ($col === 'rank') ? $finalRank : (${$col} ?? '');
                if (trim($oldVal) !== trim($newVal)) {
                    $changes[] = "{$lbl}: '{$oldVal}' -> '{$newVal}'";
                }
            }

            if (!empty($changes)) {
                $logDetails = "Updated Tournament Record for " . $athlete['full_name'] . " (Reg No: " . $athlete['regn_no'] . ") | Changes: " . implode(', ', $changes);
                logAction($pdo, 'athlete_history_updated', 'athletes', $athleteId, $logDetails);
            }

            $pdo->commit();
            echo json_encode(['success' => 'Tournament performance record updated successfully.']);
            exit();
        }
    } 
    
    elseif ($action === 'delete') {
        $historyId = isset($_POST['history_id']) ? (int)$_POST['history_id'] : 0;
        $athleteId = isset($_POST['athlete_id']) ? (int)$_POST['athlete_id'] : 0;

        if ($historyId <= 0 || $athleteId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing identifiers for record archiving.']);
            exit();
        }

        // Fetch record to retrieve details for logs
        $oldStmt = $pdo->prepare("SELECT * FROM athlete_history WHERE id = ? AND athlete_id = ? AND deleted_at IS NULL");
        $oldStmt->execute([$historyId, $athleteId]);
        $record = $oldStmt->fetch();
        if (!$record) {
            http_response_code(404);
            echo json_encode(['error' => 'History record not found or already archived.']);
            exit();
        }

        $athStmt = $pdo->prepare("SELECT full_name, regn_no FROM athletes WHERE id = ?");
        $athStmt->execute([$athleteId]);
        $athlete = $athStmt->fetch();

        $pdo->beginTransaction();

        $del = $pdo->prepare("UPDATE athlete_history SET deleted_at = CURRENT_TIMESTAMP WHERE id = ? AND athlete_id = ?");
        $del->execute([$historyId, $athleteId]);

        // Audit Log
        $logDetails = "Archived (Soft Deleted) Tournament Record for " . $athlete['full_name'] . " (Reg No: " . $athlete['regn_no'] . ") | Event: {$record['event_name']} | Year: {$record['event_year']} | Level: {$record['event_level']}";
        logAction($pdo, 'athlete_history_deleted', 'athletes', $athleteId, $logDetails);

        $pdo->commit();
        echo json_encode(['success' => 'Tournament performance record archived successfully.']);
        exit();
    } 
    
    else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid administrative action specified.']);
        exit();
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Server error occurred: ' . $e->getMessage()]);
}
