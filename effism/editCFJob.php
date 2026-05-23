<?php

require_once "jwt_auth.php";
$auth = requireAuth();
$userId = $auth->uid;

$report_id    = trim($_POST['workreport_id'] ?? '');
if (empty($report_id))
{
    echo json_encode(["status" => false, "message" => "Date is required."]);
    exit;
}

$date_report = trim($_POST['date'] ?? '');
if (empty($date_report))
{
    echo json_encode(["status" => false, "message" => "Date is required."]);
    exit;
}

$check = $mysqli->prepare("SELECT workreport_id FROM tbl_workreports WHERE workreport_id = ? AND user_id = ? AND date_report = ?");
$check->bind_param("iis", $report_id, $userId, $date_report);
$check->execute();
$checkResult = $check->get_result();

if ($checkResult->num_rows == 0) {
    echo json_encode(["status" => false, "message" => "No job found."]);
    exit;
}

$report_id   = $checkResult->fetch_assoc()['workreport_id'];
$est_time    = trim($_POST['est_time']    ?? '');
$act_time    = trim($_POST['act_time']    ?? '');
$description = trim($_POST['description'] ?? '');
$cf_date     = trim(!empty($_POST['cf_date']) ? $_POST['cf_date'] : ($_GET['cf_date'] ?? ''));
$status      = trim($_POST['status']  ?? '');
$act_time    = !empty($act_time) ? $act_time : null;

$stmt = $mysqli->prepare("UPDATE tbl_workreports SET est_time=?, act_time=?, description=?, 
cf_date=?, status=? WHERE workreport_id = ?");

$stmt->bind_param("sssssi", $est_time, $act_time, $description, $cf_date, $status, $report_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(["status" => true, "message" => "CF job saved successfully."]);
} else {
    echo json_encode(["status" => false, "message" => "Failed to save CF job: " . $stmt->error]);
}

?>