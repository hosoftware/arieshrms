<?php

require_once "jwt_auth.php";
$auth = requireAuth();

header('Content-Type: application/json');

//$year  = trim($_POST['year'] ?? date('Y'));
$month = trim($_POST['month'] ?? '');
$year  = !empty($_POST['year'])  ? trim($_POST['year'])  : date('Y');
$user_id = $auth->uid;

$grp = $mysqli->prepare("SELECT group_id,hr_id FROM tbl_users WHERE user_id = ?");
$grp->bind_param("i", $user_id);
$grp->execute();
$grpResult = $grp->get_result();
$group = $grpResult->fetch_assoc();

$group_value=$group['group_id'];


$hr_id = $group['hr_id'];
$grp->close();

if($group_value==1)
{
    $dbtable = "`0_salary`";
}
else if($group_value==2)
{
    $dbtable = "`0_cochin_salary`";
}
else
{
    echo json_encode([
        "status" => false,
        "message"  => "User group id is missing."
    ]);    
    exit;   
}

$sql = "SELECT * FROM $dbtable WHERE emp_id='$hr_id' and year=?";
$params = [$year];
$types  = "i";

if ($month != '') {
    $sql .= " AND month=?";
    $params[] = $month;
    $types   .= "i";
}

$stmt = $mysqli->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();

$CheckResult = $stmt->get_result();
$rows = $CheckResult->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$data = [];

foreach ($rows as $row) {
    $month = $row['month'];

    $data[] = [
        "year"  => $year,
        "month" => $month,
        "pdf"   => "www.efftime.com/webservices/arieshrms/salary/salary_slip.php?user_id={$user_id}&month={$month}&year={$year}"
    ];
}

echo json_encode([
    "status" => true,
    "count"  => count($data),
    "data"   => $data
]);