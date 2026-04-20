<?php

require_once "jwt_auth.php";
$auth = requireAuth();

$userId = $auth->uid;

$date = trim($_POST['date'] ?? '');
if (empty($date)) {
    echo json_encode(["status" => false, "message" => "Date is required."]);
    exit;
}

$sql = "SELECT w.workreport_id, w.taskname, w.est_time, w.act_time, w.status, w.job_no, w.description, 
        m.main_type_name AS mian_type, s.job_type_name AS sub_type  
        FROM tbl_workreports w
        LEFT JOIN tbl_main_type m ON m.main_type_id=w.main_type
        LEFT JOIN tbl_job_type s ON s.id=w.job_type
        WHERE w.user_id = ? AND w.date_report = ? 
        ORDER BY w.row_id ASC";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("is", $userId, $date);
$stmt->execute();
$result = $stmt->get_result();

$jobs = [];
while ($row = $result->fetch_assoc()) {
    $jobs[] = $row;
}

echo json_encode([
    "status" => true,
    "data" => $jobs
]);

$stmt->close();
