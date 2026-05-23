<?php

require_once "jwt_auth.php";
$auth = requireAuth();
$userId = $auth->uid;

$job_id   = trim($_POST['job_id'] ?? '');
if (empty($job_id)) {
    echo json_encode(["status" => false, "message" => "Job ID is required."]);
    exit;
}

$job_date = trim($_POST['date'] ?? '');
if (empty($job_date)) {
    echo json_encode(["status" => false, "message" => "Job date is required."]);
    exit;
}

$est_time = trim($_POST['est_time'] ?? '');
$act_time = trim($_POST['act_time'] ?? '');
$remarks  = trim($_POST['remarks']  ?? '');

$check = $mysqli->prepare("SELECT id FROM tbl_daily_job_status WHERE job_id = ? AND job_date = ? AND user_id = ?");
$check->bind_param("isi", $job_id, $job_date, $userId);
$check->execute();
$checkResult = $check->get_result();

if ($checkResult->num_rows > 0) {
    $stmt = $mysqli->prepare("UPDATE tbl_daily_job_status SET est_time=?, act_time=?, remarks=? WHERE job_id=? AND job_date=? AND user_id=?");
    $stmt->bind_param("sssisi", $est_time, $act_time, $remarks, $job_id, $job_date, $userId);
   
} 
else {
    $stmt = $mysqli->prepare("INSERT INTO tbl_daily_job_status (job_id, job_date, est_time, act_time, user_id, remarks) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssis", $job_id, $job_date, $est_time, $act_time, $userId, $remarks);
}

if ($stmt->execute()) {
    echo json_encode(["status" => true, "message" => "Routine job saved successfully."]);
} else {
    echo json_encode(["status" => false, "message" => "Failed to save routine job: " . $stmt->error]);
}

$stmt->close();

?>