<?php

require_once "jwt_auth.php";
$auth = requireAuth();

$userId = $auth->uid;

$date = trim($_POST['date'] ?? '');
if (empty($date)) {
    echo json_encode(["status" => false, "message" => "Date is required."]);
    exit;
}

function fetchRows($mysqli, $sql, $types, ...$params)
{
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    $stmt->close();
    return $rows;
}

// routine jobs
$sqlr = "SELECT j.id,
j.job_name, m.main_type_id AS main_type_name, jt.id AS job_type_name, 
COALESCE(s.est_time, j.est_time) AS est_time, COALESCE(s.act_time, NULL) AS act_time, s.remarks
FROM tbl_daily_jobs j 
LEFT JOIN tbl_daily_job_status s ON s.job_id = j.id AND s.job_date = ?
LEFT JOIN tbl_main_type m ON m.main_type_id = j.main_type 
LEFT JOIN tbl_job_type jt ON jt.id = j.sub_type 
WHERE j.user_id=? AND j.status=1 AND j.auth_status=1";

$routineJobs = fetchRows($mysqli, $sqlr, "si", $date, $userId);

$workreportBase = "SELECT w.workreport_id, w.taskname, w.est_time, w.act_time, w.status, w.job_no, w.description, 
        m.main_type_id AS mian_type, s.id AS sub_type, w.target_date, w.cf_date
        FROM tbl_workreports w
        LEFT JOIN tbl_main_type m ON m.main_type_id=w.main_type
        LEFT JOIN tbl_job_type s ON s.id=w.job_type
        WHERE w.user_id = ? AND w.date_report = ? AND w.taskname!=''";

// Cf jobs
$sqlcf = $workreportBase . " AND w.is_carry=1 AND w.delegation_id=0 ORDER BY w.taskname ASC";
$cfJobs = fetchRows($mysqli, $sqlcf, "is", $userId, $date);

// Delegated jobs
$sqld = $workreportBase . " AND w.delegation_id>0 ORDER BY w.row_id ASC";
$delegatedJobs = fetchRows($mysqli, $sqld, "is", $userId, $date);

// Daily jobs
$sql = $workreportBase . " AND w.is_carry NOT IN (1, 3) AND w.delegation_id=0 ORDER BY w.row_id ASC";
$jobs = fetchRows($mysqli, $sql, "is", $userId, $date);


echo json_encode([
    "status" => true,
    "routineJobs" => $routineJobs,
    "cfJobs" => $cfJobs,
    "delegatedJobs" => $delegatedJobs,
    "jobs" => $jobs
]);