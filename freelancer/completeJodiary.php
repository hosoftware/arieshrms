<?php

require_once "jwt_auth.php";
require_once "connect.inc.php";

$auth = requireAuth();
$userId = $auth->uid;

$datetime = date("Y-m-d H:i:s");

$date = trim($_POST['date'] ?? '');
if (empty($date)) {
    echo json_encode(["status" => false, "message" => "Date is required."]);
    exit;
}

// 1. Fetch total act_time from tbl_workreports for this user and date
$stmtAct = $mysqli->prepare("SELECT COALESCE(SUM(TIME_TO_SEC(act_time)), 0) as total_seconds FROM tbl_workreports WHERE user_id = ? AND date_report = ?");
$stmtAct->bind_param("is", $userId, $date);
$stmtAct->execute();
$total_job_seconds = $stmtAct->get_result()->fetch_assoc()['total_seconds'];
$stmtAct->close();

$total_job = secondsToTime($total_job_seconds);

// 2. Constants for freelancer completion
$time_in = "08:00:00";
$nwt = "01:00:00";
$work_status = "site";

// 3. Calculate time_out and net_time
$time_in_seconds = timeToSeconds($time_in);
$nwt_seconds = timeToSeconds($nwt);
$time_out_seconds = $time_in_seconds + $total_job_seconds + $nwt_seconds;
$time_out = secondsToTime($time_out_seconds);

$net_time = $total_job; // Net working time equals total job time

// 4. Upsert into tbl_time
$check = $mysqli->prepare("SELECT id FROM tbl_time WHERE user_id = ? AND date_log = ?");
$check->bind_param("is", $userId, $date);
$check->execute();
$exists = $check->get_result()->num_rows > 0;
$check->close();

$is_bulk=4;

if ($exists) {
    $sql = "UPDATE tbl_time SET 
                work_status = ?, 
                time_in = ?, 
                time_out = ?, 
                nwt = ?, 
                net_time = ?, 
                total_job = ?,
                completion_time = ?,
                is_bulk = $is_bulk,
                is_complete = 1 
            WHERE user_id = ? AND date_log = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("sssssssis", $work_status, $time_in, $time_out, $nwt, $net_time, $total_job, $datetime, $userId, $date);
} else {
    $sql = "INSERT INTO tbl_time (user_id, date_log, work_status, time_in, time_out, nwt, net_time, total_job, is_complete, completion_time, is_bulk) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, $is_bulk)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("issssssss", $userId, $date, $work_status, $time_in, $time_out, $nwt, $net_time, $total_job, $datetime);
}

if ($stmt->execute()) {
    echo json_encode([
        "status" => true,
        "message" => "Job diary completed successfully.",
        "data" => [
            "net_time" => $total_job,
        ]
    ]);
} else {
    echo json_encode(["status" => false, "message" => "Failed to complete job diary: " . $stmt->error]);
}
$stmt->close();

// Helper functions
function timeToSeconds($time)
{
    if (empty($time))
        return 0;
    $parts = explode(':', $time);
    $parts = array_pad($parts, 3, 0);
    return ((int) $parts[0] * 3600) + ((int) $parts[1] * 60) + (int) $parts[2];
}

function secondsToTime($seconds)
{
    $h = floor($seconds / 3600);
    $m = floor(($seconds % 3600) / 60);
    $s = $seconds % 60;
    return sprintf("%02d:%02d:%02d", $h, $m, $s);
}
?>