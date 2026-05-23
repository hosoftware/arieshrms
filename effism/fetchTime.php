<?php

require_once "jwt_auth.php";
$auth = requireAuth();

$userId = $auth->uid;

$date = trim($_POST['date'] ?? '');
if (empty($date)) {
    echo json_encode(["status" => false, "message" => "Date is required."]);
    exit;
}

$sql = "SELECT t.id, t.date_log, t.time_in, t.time_out, t.nwt, t.work_status, t.leave_type, t.site_travel, t.night, t.home, t.remarks, t.location,
               t.health, t.family, t.friend, t.sleep, t.travel, t.not_punctual, t.late_remarks, u.reporting_time
        FROM tbl_time t
        LEFT JOIN tbl_users u ON t.user_id = u.user_id
        WHERE t.user_id = ? AND t.date_log = ? 
        LIMIT 1";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("is", $userId, $date);
$stmt->execute();
$result = $stmt->get_result();
$timeData = $result->fetch_assoc();

if ($timeData) {
    $is_late = false;
    if (!empty($timeData['reporting_time']) && !empty($timeData['time_in']) && $timeData['time_in'] !== '00:00:00') {
        $reporting_time_24 = date("H:i:s", strtotime($timeData['reporting_time']));
        $time_in_norm = date("H:i:s", strtotime($timeData['time_in']));
        if ($time_in_norm > $reporting_time_24) {
            $is_late = true;
        }
    }
    $timeData['is_late'] = $is_late;

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
