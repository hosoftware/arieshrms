<?php

require_once "jwt_auth.php";
$auth = requireAuth();

$user_id = $auth->uid;
$date_log = $_POST['date'] ?? '';
$work_status = $_POST['work_status'] ?? '';
if($work_status=='WFH' || $work_status=='wfh')
{
    $work_status = strtolower($work_status);
}
$leave_type = $_POST['leave_type'] ?? '';
$time_in = $_POST['time_in'] ?? '00:00:00';
$time_out = $_POST['time_out'] ?? '00:00:00';
$nwt = $_POST['nwt'] ?? '00:00:00';
$extra_break = $_POST['extra_break'] ?? '00:00:00';
$site_travel = $_POST['site_travel'] ?? '00:00:00';
$night = $_POST['night'] ?? '00:00:00';
$home = $_POST['home'] ?? '00:00:00';
$leave_hours = $_POST['leave_hours'] ?? '00:00:00';
$remarks = trim($_POST['remarks'] ?? '');
$location = trim($_POST['location'] ?? '');
$health = $_POST['health'] ?? '';
$family = $_POST['family'] ?? '';
$friend = $_POST['friend'] ?? '';
$sleep = $_POST['sleep'] ?? '';
$travel = $_POST['travel'] ?? '';
$not_punctual = $_POST['not_punctual'] ?? 0;
$late_remarks = trim($_POST['late_remarks'] ?? '');
$late_remark_ok = true;

// Calculate lateness automatically
$is_late = 0;
$userQuery = "SELECT reporting_time FROM tbl_users WHERE user_id = ?";
$uStmt = $mysqli->prepare($userQuery);
$uStmt->bind_param("i", $user_id);
$uStmt->execute();
$uRes = $uStmt->get_result();
$uData = $uRes->fetch_assoc();
if ($uData && !empty($uData['reporting_time']) && !empty($time_in) && $time_in !== '00:00:00') {
    $reporting_time_24 = date("H:i:s", strtotime($uData['reporting_time']));
    $time_in_norm = date("H:i:s", strtotime($time_in));

    if ($time_in_norm > $reporting_time_24) {
        $is_late = 1;
    }
}
$uStmt->close();

// If manually marked or automatically detected
$not_punctual = ($not_punctual == 1 || $is_late == 1) ? 1 : 0;

if ($date_log == '') {
    echo json_encode([
        "status" => false,
        "message" => "Date is required.",
        "is_late" => (bool) $is_late,
        "late_remark" => $late_remark_ok
    ]);
    exit;
}

$net_seconds = timeToSeconds($time_out) - timeToSeconds($time_in);
$net_seconds -= timeToSeconds($nwt);
$net_seconds -= timeToSeconds($extra_break);
$net_seconds += timeToSeconds($night);
$net_seconds += timeToSeconds($home);
$net_seconds += timeToSeconds($leave_hours);
$net_seconds -= timeToSeconds($site_travel);

$net_time = secondsToTime(max(0, $net_seconds));

// Check if record exists and fetch existing late remarks
$checkSql = "SELECT id, late_remarks FROM tbl_time WHERE user_id = ? AND date_log = ?";
$checkStmt = $mysqli->prepare($checkSql);
$checkStmt->bind_param("is", $user_id, $date_log);
$checkStmt->execute();
$checkRes = $checkStmt->get_result();
$existingRecord = $checkRes->fetch_assoc();
$checkStmt->close();

$recordExists = $existingRecord ? true : false;
$existing_late_remarks = $existingRecord['late_remarks'] ?? '';

// If late, require late_remarks unless it's already in the database
if ($is_late == 1 && empty($late_remarks)) {
    if (!$recordExists || empty($existing_late_remarks)) {
        $late_remark_ok = false;
    }
}

// Preserve existing late remarks if not provided in the current request
// if (empty($late_remarks) && !empty($existing_late_remarks)) {
//     $late_remarks = $existing_late_remarks;
// }

if ($recordExists) {
    // Record exists — UPDATE
    $sql = "UPDATE tbl_time SET time_in = ?, time_out = ?, nwt = ?, extra_break = ?, work_status = ?, site_travel = ?, night = ?, home = ?, leave_hours = ?, leave_type = ?, net_time = ?, remarks = ?, location = ?,
             health = ?, family = ?, friend = ?, sleep = ?, travel = ?, not_punctual = ?, late_remarks = ?
             WHERE user_id = ? AND date_log = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param(
        "ssssssssssssssssssisis",
        $time_in,
        $time_out,
        $nwt,
        $extra_break,
        $work_status,
        $site_travel,
        $night,
        $home,
        $leave_hours,
        $leave_type,
        $net_time,
        $remarks,
        $location,
        $health,
        $family,
        $friend,
        $sleep,
        $travel,
        $not_punctual,
        $late_remarks,
        $user_id,
        $date_log
    );
} else {
    // Record does not exist — INSERT
    $sql = "INSERT INTO tbl_time (user_id, date_log, time_in, time_out, nwt, extra_break, work_status, site_travel, night, home, leave_hours, leave_type, net_time, remarks, location, health, family, friend, sleep, travel, not_punctual, late_remarks)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param(
        "isssssssssssssssssssis",
        $user_id,
        $date_log,
        $time_in,
        $time_out,
        $nwt,
        $extra_break,
        $work_status,
        $site_travel,
        $night,
        $home,
        $leave_hours,
        $leave_type,
        $net_time,
        $remarks,
        $location,
        $health,
        $family,
        $friend,
        $sleep,
        $travel,
        $not_punctual,
        $late_remarks
    );
}

if ($stmt->execute()) {
    echo json_encode([
        "status" => true,
        "message" => $recordExists ? "Time record updated successfully." : "Time record saved successfully.",
        "is_late" => (bool) $is_late,
        "late_remark" => $late_remark_ok
    ]);
} else {
    echo json_encode([
        "status" => false,
        "message" => "Failed to save time record: " . $stmt->error,
        "is_late" => (bool) $is_late,
        "late_remark" => $late_remark_ok
    ]);
}
$stmt->close();

function timeToSeconds($time)
{
    if (empty($time)) {
        $time = "00:00:00";
    }
    if (substr_count($time, ':') === 1) {
        $time .= ":00";
    }
    $parts = explode(':', $time);
    $parts = array_pad($parts, 3, 0);
    list($hours, $minutes, $seconds) = $parts;
    return ((int) $hours * 3600) + ((int) $minutes * 60) + (int) $seconds;
}

function secondsToTime($seconds)
{
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    return sprintf("%02d:%02d:%02d", $hours, $minutes, $secs);
}