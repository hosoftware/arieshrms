<?php

require_once "jwt_auth.php";
$auth = requireAuth();
$userId = $auth->uid;

$date = trim($_POST['date'] ?? '');
if (empty($date)) {
    echo json_encode(["status" => false, "message" => "Date is required."]);
    exit;
}

$timeIn = "08:00:00";
$nwt = "01:00:00";

$timeInSeconds = timeToSeconds($timeIn);
$nwtSeconds = timeToSeconds($nwt);
$totalJobSeconds = 0;

$stmt = $mysqli->prepare("SELECT act_time FROM tbl_workreports WHERE user_id = ? AND date_report = ? AND taskname!=''");
$stmt->bind_param("is", $userId, $date);
$stmt->execute();
$result = $stmt->get_result();
$rowCount = $result->num_rows;

while ($row = $result->fetch_assoc()) {
    $totalJobSeconds += timeToSeconds($row['act_time']);
}
$stmt->close();

$totalJob = secondsToTime($totalJobSeconds);
$timeOutSeconds = $timeInSeconds + $totalJobSeconds + $nwtSeconds;
$timeOut = secondsToTime($timeOutSeconds);
$netTime = $totalJob;

echo json_encode([
    "status" => true,
    "jobCount" => $rowCount,
    "total_job" => $totalJob,
    "net_time" => $netTime,
]);

function timeToSeconds($time)
{
    if (empty($time))
        return 0;
    $parts = explode(':', $time);
    $parts = array_pad($parts, 3, 0);
    return ((int) $parts[0] * 3600) + ((int) $parts[1] * 60) + (int) $parts[2];
}

function secondsToTime($seconds)
{
    $h = floor($seconds / 3600);
    $m = floor(($seconds % 3600) / 60);
    $s = $seconds % 60;
    return sprintf("%02d:%02d:%02d", $h, $m, $s);
}

?>