<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once "connect.inc.php";

require_once "jwt_auth.php";

$action = isset($_REQUEST['action']) ? trim($_REQUEST['action']) : '';

switch ($action) {

    case "login":
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($username == '' || $password == '') {
            echo json_encode([
                "status" => false,
                "message" => "Username and password required"
            ]);
            exit;
        }

        $sql = "SELECT
            u.user_id, u.username, u.credential, u.full_name, u.display_name, u.employee_code,
            u.status, u.designation, u.gender, u.dob, u.email, u.personal_email,
            u.hourly_rate, u.is_lock, u.user_photo, wc.work_category_name, w.time_zone, u.gdoj,
            ROUND(TIMESTAMPDIFF(MONTH, u.gdoj, CURDATE()) / 12, 1) AS years_in_aries,
            u2.full_name AS parent_name, d1.short_name AS emp_company_name,
            d2.short_name AS emp_division_name, d3.short_name AS emp_subdivision_name,
            emptyp.name AS emp_type_name, w.work_place AS work_location
        FROM tbl_users u
        LEFT JOIN tbl_users u2 ON u.parent_id = u2.user_id
        LEFT JOIN tbl_dimensions d1 ON u.emp_company_id = d1.id AND d1.dimension_type = 1
        LEFT JOIN tbl_dimensions d2 ON u.emp_division_id = d2.id AND d2.dimension_type = 2
        LEFT JOIN tbl_dimensions d3 ON u.emp_subdivision_id = d3.id AND d3.dimension_type = 3
        LEFT JOIN tbl_emp_type emptyp ON u.emp_type = emptyp.id
        LEFT JOIN tbl_emp_workplace w ON u.work_location = w.id
        LEFT JOIN tbl_work_category wc ON wc.id = u.work_category
        WHERE u.username = ?";

        $stmt = $mysqli->prepare($sql);

        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            echo json_encode([
                "status" => false,
                "message" => "User not found"
            ]);
            exit;
        }

        $user = $result->fetch_assoc();

        if (!password_verify($password, $user['credential'])) {
            echo json_encode([
                "status" => false,
                "message" => "Invalid credentials"
            ]);
            exit;
        }

        if ($user['status'] != "Active") {
            echo json_encode([
                "status" => false,
                "message" => "User not active"
            ]);
            exit;
        }

        $token = generateToken([
            'user_id' => $user['user_id'],
            'username' => $user['username'],
            'timezone' => $user['time_zone'],
        ]);

        $user['profile_img_url'] = "https://www.effism.com/images/employee/" . $user['user_photo'];
        unset($user['credential']);

        echo json_encode([
            "status" => true,
            "message" => "Login successful",
            "token" => $token,
            "data" => $user
        ]);
        break;

    case "getLeaveTypes":
        require_once "jwt_auth.php";
        $auth = requireAuth();
        $userId = $auth->uid;

        $stmtParent = $mysqli->prepare("SELECT COUNT(*) as cnt FROM tbl_users WHERE parent_id = ? AND status='Active'");
        $stmtParent->bind_param("i", $userId);
        $stmtParent->execute();
        $parentResult = $stmtParent->get_result()->fetch_assoc();
        $isParent = $parentResult['cnt'] > 0;

        if ($isParent) {
            // Parent gets all leave types
            $stmt = $mysqli->prepare("SELECT id, type FROM tbl_lock_types ORDER BY sort_order ASC");
            $stmt->execute();
        } else {
            // Non-parent gets gender-filtered leave types
            $gender = UserData($userId, "gender", $mysqli);
            $type = $gender == 'Male' ? 3 : 2;

            $stmt = $mysqli->prepare("SELECT id, type FROM tbl_lock_types WHERE open_to IN (1, ?) ORDER BY sort_order ASC");
            $stmt->bind_param("i", $type);
            $stmt->execute();
        }

        $result = $stmt->get_result();
        $leaveTypes = [];
        while ($row = $result->fetch_assoc()) {
            $leaveTypes[] = $row;
        }

        echo json_encode([
            "status" => true,
            "data" => $leaveTypes
        ]);
        break;

    case "getUsers":
        require_once "jwt_auth.php";
        $auth = requireAuth();
        $userId = $auth->uid;

        $sql = "SELECT user_id, CONCAT(full_name, ' - ', employee_code) AS full_name_code
        FROM tbl_users WHERE status = 'Active' AND (user_id = ? OR parent_id = ?)
        ORDER BY full_name ASC";

        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ii", $userId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        echo json_encode([
            "status" => true,
            "data" => $users
        ]);
        break;

    case "lockUser":
        require_once "jwt_auth.php";
        $auth = requireAuth();
        ini_set('date.timezone', getTimezone($auth));

        $locked_user = trim($_POST['locked_user'] ?? '');
        $type = trim($_POST['type'] ?? '');
        $from_date = trim($_POST['from_date'] ?? '');
        $to_date = trim($_POST['to_date'] ?? '');
        $remarks = trim($_POST['remarks'] ?? '');
        $contact_number = trim($_POST['contact_number'] ?? '');
        $coff_date = trim($_POST['coff_date'] ?? '');
        $userId = $auth->uid;
        $datetime = date("Y-m-d H:i:s");

        if ($type == 13 && $coff_date == '') // coff-date is required when type is Compensatory Off
        {
            echo json_encode([
                "status" => false,
                "message" => "Compensatory Off date is required."
            ]);
            exit;
        } else {
            $coff_date = '';
        }

        $lck = $mysqli->prepare("UPDATE tbl_users SET is_lock = 1 WHERE user_id = ?");
        $lck->bind_param("i", $locked_user);
        $lck->execute();

        $sql = "INSERT INTO tbl_user_lock (remarks, type, locked_user, locked_by, from_date, to_date, lock_date, contact_number, coff_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("siiisssss", $remarks, $type, $locked_user, $userId, $from_date, $to_date, $datetime, $contact_number, $coff_date);
        if ($stmt->execute()) {
            echo json_encode([
                "status" => true,
                "message" => "User lock request submitted successfully."
            ]);
        } else {
            echo json_encode([
                "status" => false,
                "message" => "Failed to submit user lock request."
            ]);
        }
        break;

    case "breakStatus":
        require_once "jwt_auth.php";
        $auth = requireAuth();
        $decision = DecideButton($mysqli, $auth);

        echo json_encode([
            "status" => true,
            "break_status" => $decision['button'] == "IN" ? 1 : 0
        ]);
        break;

    case "validateQR":
        require_once "jwt_auth.php";
        $auth = requireAuth();

        $qr_value = trim($_POST['qr_value'] ?? '');
        validateQR($mysqli, $qr_value);

        $decision = DecideButton($mysqli, $auth);

        echo json_encode([
            "status" => true,
            "message" => "QR code is valid.",
            "button" => $decision['button']
        ]);
        break;

    case "breakOut":
        require_once "jwt_auth.php";
        $auth = requireAuth();
        ini_set('date.timezone', getTimezone($auth));

        $qr_value = trim($_POST['qr_value'] ?? '');
        validateQR($mysqli, $qr_value);

        $reason = trim($_POST['reason'] ?? '');
        $user_id = $auth->uid;
        $now = date('Y-m-d H:i:s');
        $today = date('Y-m-d');

        if ($reason === '') {
            echo json_encode([
                "status" => false,
                "message" => "Reason is required for break OUT."
            ]);
            exit;
        }

        $stmt = $mysqli->prepare("
            INSERT INTO tbl_breaktime_log (user_id, break_out, reason, status, date, entry_time)
            VALUES (?, ?, ?, 'open', ?, ?)
        ");
        $stmt->bind_param("issss", $user_id, $now, $reason, $today, $now);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $stmt->close();
            echo json_encode([
                "status" => true,
                "message" => "Break OUT recorded successfully.",
                "reason" => $reason,
                "break_out" => $now
            ]);
        } else {
            $stmt->close();
            echo json_encode([
                "status" => false,
                "message" => "Failed to record break OUT. Please try again."
            ]);
        }
        break;

    case "breakIn":
        require_once "jwt_auth.php";
        $auth = requireAuth();
        ini_set('date.timezone', getTimezone($auth));

        $qr_value = trim($_POST['qr_value'] ?? '');
        validateQR($mysqli, $qr_value);

        $now = date('Y-m-d H:i:s');
        $user_id = $auth->uid;
        $today = date('Y-m-d');

        $stmt = $mysqli->prepare("
            UPDATE tbl_breaktime_log
            SET break_in = ?, status = 'closed'
            WHERE user_id = ? AND status = 'open' AND date=?
        ");
        $stmt->bind_param("sis", $now, $user_id, $today);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $stmt->close();
            echo json_encode([
                "status" => true,
                "message" => "Break IN recorded successfully.",
                "break_in" => $now
            ]);
        } else {
            $stmt->close();
            echo json_encode([
                "status" => false,
                "message" => "Failed to record break IN. Record may already be closed."
            ]);
        }
        break;

    case "listSalary":
        require_once "salary/listSalary.php";
        break;

    case "breakLogListing":
        require_once "jwt_auth.php";
        $auth = requireAuth();

        $userId = $auth->uid;
        $date = isset($_POST['date']) ? trim($_POST['date']) : date('Y-m-d');

        $sql = "SELECT bl.break_out, bl.break_in, bl.reason, bl.status, bl.date,
        CASE 
            WHEN bl.break_in = '00:00:00' OR bl.break_out = '00:00:00' OR bl.break_in IS NULL OR bl.break_out IS NULL 
            THEN NULL
            ELSE TIMEDIFF(bl.break_in, bl.break_out)
        END AS break_time
        FROM tbl_breaktime_log bl
        LEFT JOIN tbl_users u ON u.user_id = bl.user_id
        WHERE bl.date= ? AND bl.is_delete=0 AND bl.user_id = ?
        ORDER BY bl.id DESC";

        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("si", $date, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }

        echo json_encode([
            "status" => true,
            "data" => $logs
        ]);
        break;

    case "lastWorkingDate":
        require_once "effism/lastWorkingDate.php";
        break;

    case "saveTime":
        require_once "effism/saveTime.php";
        break;

    case "addNewJob":
        require_once "effism/addNewJob.php";
        break;

    case "editJob":
        require_once "effism/editJob.php";
        break;

    case "completeJobdiary":
        require_once "effism/completeJobdiary.php";
        break;

    case "jobdiaryStatus":
        require_once "effism/jobdiaryStatus.php";
        break;

    case "listJobs":
        require_once "effism/listJobs.php";
        break;

    default:
        echo json_encode([
            "status" => false,
            "message" => "Invalid action"
        ]);
        break;
}


function UserData($userId, $col, $mysqliection)
{
    $stmt = $mysqliection->prepare("SELECT $col FROM tbl_users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        return $result->fetch_assoc()[$col];
    }
    return null;
}

function validateQR($mysqli, $qr_value)
{
    if ($qr_value == "") {
        echo json_encode([
            "status" => false,
            "message" => "QR value is missing."
        ]);
        exit;
    }

    $stmt = $mysqli->prepare("SELECT is_active, created_at, TIMESTAMPDIFF(SECOND, created_at, NOW()) AS age_seconds
        FROM tbl_breaktime_qr 
        WHERE qr_token = ? LIMIT 1");
    $stmt->bind_param("s", $qr_value);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row) {
        echo json_encode([
            "status" => false,
            "reason" => "invalid",
            "message" => "Invalid QR code. This token does not exist."
        ]);
        exit;
    }

    // Check expired by time (same condition as UPDATE in QR generator)
    if ((int) $row['age_seconds'] >= VALIDITY_SECONDS) {
        echo json_encode([
            "status" => false,
            "reason" => "expired",
            "message" => "QR code has expired. Please scan the latest QR code."
        ]);
        exit;
    }

    // Check is_active flag
    if ((int) $row['is_active'] === 0) {
        echo json_encode([
            "status" => false,
            "reason" => "expired",
            "message" => "QR code has expired. Please scan the latest QR code."
        ]);
        exit;
    }
}


function DecideButton(mysqli $mysqli, $auth)
{
    $userId = $auth->uid;
    ini_set('date.timezone', getTimezone($auth));
    $today = date('Y-m-d');

    // Get the most recent entry for today
    $stmt = $mysqli->prepare("
        SELECT id, status FROM tbl_breaktime_log
        WHERE user_id = ?
        AND date = ?
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->bind_param("is", $userId, $today);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row && $row['status'] === 'open') {
        return [
            "status" => true,
            "button" => "IN",
            "message" => "Employee is currently on break."
        ];
    }

    return [
        "status" => true,
        "button" => "OUT",
        "message" => "Employee is not on break."
    ];
}