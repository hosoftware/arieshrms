<?php

require_once "jwt_auth.php";
$auth = requireAuth();

$userId = $auth->uid;
ini_set('date.timezone', getTimezone($auth));

$date_report = trim($_POST['date'] ?? '');
if (empty($date_report)) {
    echo json_encode(["status" => false, "message" => "Date is required."]);
    exit;
}

$datetime = date("Y-m-d H:i:s");

// 1. Fetch time data from tbl_time
$stmtTblTime = $mysqli->prepare("
    SELECT time_in, time_out, nwt, extra_break, home, night,
           site_travel, leave_hours, work_status
    FROM   tbl_time
    WHERE  user_id  = ?
      AND  date_log = ?
    LIMIT  1
");
$stmtTblTime->bind_param("is", $userId, $date_report);
$stmtTblTime->execute();
$timeRow = $stmtTblTime->get_result()->fetch_assoc();
$stmtTblTime->close();

if (!$timeRow) {
    echo json_encode([
        "status" => false,
        "message" => "No time record found for the given date."
    ]);
    exit;
}

$time_in     = !empty($timeRow['time_in'])     ? $timeRow['time_in']     : "00:00:00";
$time_out    = !empty($timeRow['time_out'])    ? $timeRow['time_out']    : "00:00:00";
$nwt         = !empty($timeRow['nwt'])         ? $timeRow['nwt']         : "00:00:00";
$extra_break = !empty($timeRow['extra_break']) ? $timeRow['extra_break'] : "00:00:00";
$home        = !empty($timeRow['home'])        ? $timeRow['home']        : "00:00:00";
$night       = !empty($timeRow['night'])       ? $timeRow['night']       : "00:00:00";
$site_travel = !empty($timeRow['site_travel']) ? $timeRow['site_travel'] : "00:00:00";
$leave_hours = !empty($timeRow['leave_hours']) ? $timeRow['leave_hours'] : "00:00:00";
$work_status = $timeRow['work_status'];

if (timeToSeconds($time_in) > timeToSeconds($time_out)) {
    echo json_encode([
        "status" => false,
        "message" => "Duty end time must be greater than duty start time."
    ]);
    exit;
}

$net_seconds  = timeToSeconds($time_out) - timeToSeconds($time_in);
$net_seconds -= timeToSeconds($nwt);
$net_seconds -= timeToSeconds($extra_break);
$net_seconds += timeToSeconds($night);
$net_seconds += timeToSeconds($home);
$net_seconds += timeToSeconds($leave_hours);
$net_seconds -= timeToSeconds($site_travel);

if ($work_status !== "holiday" && $net_seconds < 0) {
    echo json_encode([
        "status" => false,
        "message" => "Net time should be greater than 0.",
        "net_time" => secondsToTime($net_seconds)
    ]);
    exit;
}

// 2. Calculate total_job (sum of all act_time)
$stmtActTime = $mysqli->prepare("
    SELECT COALESCE(SUM(TIME_TO_SEC(act_time)), 0) AS total_act_seconds
    FROM   tbl_workreports
    WHERE  user_id     = ?
      AND  date_report = ?
");
$stmtActTime->bind_param("is", $userId, $date_report);
$stmtActTime->execute();
$actTimeRow = $stmtActTime->get_result()->fetch_assoc();
$stmtActTime->close();

$total_job_seconds = (int) $actTimeRow['total_act_seconds'];
$total_job         = secondsToTime($total_job_seconds);

// 3. Validate total_job time
if ($total_job_seconds > $net_seconds) {
    echo json_encode([
        "status" => false,
        "message" => "Invalid Total Job Time! Job time exceeds net time.",
        "total_job" => $total_job,
        "net_time" => secondsToTime($net_seconds)
    ]);
    exit;
}

// 4. Mark tbl_time as complete
$netTime  = secondsToTime($net_seconds);
$stmtTime = $mysqli->prepare("
    UPDATE tbl_time
    SET    is_complete     = 1,
           completion_time = ?, 
           net_time = ?, 
           total_job = ?
    WHERE  user_id = ?
      AND  date_log = ?
");
$stmtTime->bind_param("sssis", $datetime, $netTime, $total_job, $userId, $date_report);

if ($stmtTime->execute()) {
    echo json_encode([
        "status" => true,
        "message" => "Job diary completed successfully.",
        "net_time" => $netTime,
        "total_job" => $total_job
    ]);
} else {
    echo json_encode([
        "status" => false,
        "message" => "Failed to complete job diary: " . $stmtTime->error
    ]);
}
$stmtTime->close();

function timeToSeconds($time) {
    if (empty($time)) {
        $time = "00:00:00";
    }
    if (substr_count($time, ':') === 1) {
        $time .= ":00";
    }
    $parts = explode(':', $time);
    $parts = array_pad($parts, 3, 0);
    list($hours, $minutes, $seconds) = $parts;
    return ((int)$hours * 3600) + ((int)$minutes * 60) + (int)$seconds;
}

function secondsToTime($seconds) {
    $hours   = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs    = $seconds % 60;
    return sprintf("%02d:%02d:%02d", $hours, $minutes, $secs);
}
