<?php

require_once "jwt_auth.php";
$auth = requireAuth();

$userId = $auth->uid;

$date_report = trim($_POST['date'] ?? '');
if (empty($date_report)) {
    echo json_encode(["status" => false, "message" => "Date is required."]);
    exit;
}

$report_id = trim($_POST['workreport_id'] ?? '');
if (empty($report_id)) {
    echo json_encode(["status" => false, "message" => "Workreport ID is required."]);
    exit;
}

// Check if the record exists and belongs to the user
$check = $mysqli->prepare("SELECT workreport_id FROM tbl_workreports WHERE workreport_id = ? AND user_id = ? AND date_report = ?");
$check->bind_param("iis", $report_id, $userId, $date_report);
$check->execute();
$checkResult = $check->get_result();

if ($checkResult->num_rows == 0) {
    echo json_encode(["status" => false, "message" => "Job record not found or access denied."]);
    exit;
}
$check->close();

// Collect requested POST data for update
$taskname    = trim($_POST['taskname']    ?? '');
$main_type   = trim($_POST['main_type']   ?? '');
$job_no      = trim($_POST['job_no']      ?? '');
$est_time    = trim($_POST['est_time']    ?? '');
$act_time    = trim($_POST['act_time']    ?? '');
$description = trim($_POST['description'] ?? '');
$status      = trim($_POST['status']      ?? '0');

$sql = "UPDATE tbl_workreports SET 
            taskname=?, main_type=?, job_no=?, est_time=?, act_time=?, description=?, status=? 
        WHERE workreport_id = ? AND user_id = ? AND date_report = ?";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("sssssssiis", $taskname, $main_type, $job_no, $est_time, $act_time, $description, $status, $report_id, $userId, $date_report);

if ($stmt->execute()) {
    echo json_encode([
        "status" => true,
        "message" => "Job updated successfully."
    ]);
} else {
    echo json_encode([
        "status" => false,
        "message" => "Failed to update job: " . $stmt->error
    ]);
}

$stmt->close();
