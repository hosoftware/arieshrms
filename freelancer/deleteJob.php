<?php

require_once "jwt_auth.php";
$auth = requireAuth();
$userId = $auth->uid;


$report_id = trim($_POST['workreport_id'] ?? '');
$date_report = trim($_POST['date'] ?? '');

if (empty($report_id) || empty($date_report)) {
    echo json_encode([
        "status" => false,
        "message" => "Required fields missing: workreport_id, date"
    ]);
    exit;
}

$check = $mysqli->prepare("SELECT workreport_id FROM tbl_workreports WHERE workreport_id = ? AND user_id = ? AND date_report = ?");
$check->bind_param("iis", $report_id, $userId, $date_report);
$check->execute();
if ($check->get_result()->num_rows == 0) {
    echo json_encode(["status" => false, "message" => "Job record not found or access denied."]);
    exit;
}
$check->close();

$sql = "UPDATE tbl_workreports SET act_time = '00:00:00' WHERE workreport_id = ? AND user_id = ? AND date_report = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("iis", $report_id, $userId, $date_report);
if ($stmt->execute()) {
    echo json_encode([
        "status" => true,
        "message" => "Job deleted successfully."
    ]);
} else {
    echo json_encode([
        "status" => false,
        "message" => "Failed to delete job: " . $stmt->error
    ]);
}

?>