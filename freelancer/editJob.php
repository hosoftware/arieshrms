<?php

require_once "jwt_auth.php";
$auth = requireAuth();
$userId = $auth->uid;

// Receive POST values
$report_id = trim($_POST['workreport_id'] ?? '');
$date_report = trim($_POST['date'] ?? '');
$taskname = trim($_POST['taskname'] ?? '');
$job_no = trim($_POST['job_no'] ?? '');
$act_time = trim($_POST['act_time'] ?? '');
$description = trim($_POST['description'] ?? '');
$act_edit_entry = date("Y-m-d H:i:s");

// Validate required fields
if (empty($report_id) || empty($date_report) || empty($taskname) || empty($job_no) || empty($act_time)) {
    echo json_encode([
        "status" => false,
        "message" => "Required fields missing: workreport_id, date, taskname, job_no, act_time"
    ]);
    exit;
}

// Security Check: Verify record exists and belongs to the authenticated user
$check = $mysqli->prepare("SELECT workreport_id FROM tbl_workreports WHERE workreport_id = ? AND user_id = ? AND date_report = ?");
$check->bind_param("iis", $report_id, $userId, $date_report);
$check->execute();
if ($check->get_result()->num_rows == 0) {
    echo json_encode(["status" => false, "message" => "Job record not found or access denied."]);
    exit;
}
$check->close();

// Perform the update
$sql = "UPDATE tbl_workreports SET 
            taskname = ?, 
            job_no = ?, 
            act_time = ?, 
            description = ?,
            act_entry = ?
        WHERE workreport_id = ? AND user_id = ? AND date_report = ?";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param(
    "sssssiis",
    $taskname,
    $job_no,
    $act_time,
    $description,
    $act_edit_entry,
    $report_id,
    $userId,
    $date_report
);

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
?>