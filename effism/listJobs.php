<?php

require_once "jwt_auth.php";
$auth = requireAuth();

$userId = $auth->uid;

$date = trim($_POST['date'] ?? '');
if (empty($date)) {
    echo json_encode(["status" => false, "message" => "Date is required."]);
    exit;
}

$sql = "SELECT workreport_id, taskname, est_time, act_time, status 
        FROM tbl_workreports 
        WHERE user_id = ? AND date_report = ? 
        ORDER BY row_id ASC";

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
