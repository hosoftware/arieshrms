<?php

require_once "jwt_auth.php";
$auth = requireAuth();

$userId = $auth->uid;

$date = trim($_POST['date'] ?? '');
if (empty($date)) {
    echo json_encode(["status" => false, "message" => "Date is required."]);
    exit;
}

$sql = "SELECT id, date_log, time_in, time_out, nwt, work_status, site_travel  
        FROM tbl_time 
        WHERE user_id = ? AND date_log = ? 
        LIMIT 1";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("is", $userId, $date);
$stmt->execute();
$result = $stmt->get_result();
$timeData = $result->fetch_assoc();

if ($timeData) {
    echo json_encode([
        "status" => true,
        "data" => $timeData
    ]);
} else {
    echo json_encode([
        "status" => false,
        "message" => "No time record found for the given date."
    ]);
}

$stmt->close();
