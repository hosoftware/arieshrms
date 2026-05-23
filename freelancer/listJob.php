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

$workreportBase = "SELECT 
        workreport_id, 
        taskname, 
        act_time, 
        job_no, 
        description,
        DATE_FORMAT(act_entry, '%d-%m-%Y %h:%i %p') as act_entry
    FROM tbl_workreports
    WHERE user_id = ? 
    AND date_report = ? 
    AND taskname != '' 
    AND act_time IS NOT NULL 
    AND act_time != '00:00:00'";
        
$sql = $workreportBase . " AND is_carry NOT IN (1, 3) AND delegation_id=0 ORDER BY row_id ASC";
$jobs = fetchRows($mysqli, $sql, "is", $userId, $date);

echo json_encode([
    "status" => true,
    "jobs" => $jobs
]);

?>