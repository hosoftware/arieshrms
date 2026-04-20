<?php

require_once "jwt_auth.php";
$auth = requireAuth();

$userId = $auth->uid;

$date_report = trim($_POST['date'] ?? '');
if (empty($date_report)) {
    echo json_encode(["status" => false, "message" => "Date is required."]);
    exit;
}

// Get the next row_id for this user on this date
$rwId = $mysqli->prepare("SELECT row_id FROM `tbl_workreports` WHERE date_report=? AND user_id=? ORDER BY row_id DESC LIMIT 1");
$rwId->bind_param("si", $date_report, $userId);
$rwId->execute();
$rwIdResult = $rwId->get_result();
$row_id = 0;
if ($row = $rwIdResult->fetch_assoc()) {
    $row_id = (int)$row['row_id'];
}
$row_id += 1;

// Collect ONLY requested POST data
$taskname    = trim($_POST['taskname']    ?? '');
$main_type   = trim($_POST['main_type']   ?? '');
$job_no      = trim($_POST['job_no']      ?? '');
$est_time    = trim($_POST['est_time']    ?? '');
$act_time    = trim($_POST['act_time']    ?? '');
$description = trim($_POST['description'] ?? '');
$status      = trim($_POST['status']      ?? '0');
$job_type =    trim($_POST['sub_type']   ?? '');

$sql = "INSERT INTO tbl_workreports 
    (user_id, date_report, row_id, taskname, main_type, job_no, est_time, act_time, description, status, job_type) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $mysqli->prepare($sql);

$stmt->bind_param(
    "issssssssss",
    $userId, $date_report, $row_id, $taskname, $main_type, $job_no, $est_time, $act_time, $description, $status, $job_type
);

if ($stmt->execute()) {
    echo json_encode([
        "status" => true,
        "message" => "Job added successfully."
    ]);
} else {
    echo json_encode([
        "status" => false,
        "message" => "Failed to add job: " . $stmt->error
    ]);
}

$stmt->close();

