<?php

ob_start();

require_once('../connect.inc.php');
require_once('fpdf_protection.php');
require_once('../vendor/autoload.php');

$user_id = trim($_REQUEST['user_id'] ?? '');

if ($user_id == '') {
    echo "Error: Bad request";
    exit;
}

$inc_month   = trim($_REQUEST['month'] ?? '');
$inc_year    = !empty($_REQUEST['year']) ? trim($_REQUEST['year']) : date('Y');
$month_name  = date('M', strtotime($inc_year . "-" . $inc_month . "-01"));
$date_of_pay = date('d-m-Y', mktime(0, 0, 0, $inc_month + 1, 15, $inc_year));

$grp = $mysqli->prepare("SELECT emp_type, group_id FROM tbl_users WHERE user_id = ?");
$grp->bind_param("i", $user_id);
$grp->execute();
$grpResult = $grp->get_result();
$row       = $grpResult->fetch_assoc();
$group     = $row['group_id'] ?? null;
$external  = $row['emp_type'] ?? null;
$grp->close();

class PDF extends FPDF_Protection
{
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        // FIX: AliasNbPages() called separately, {nb} used as plain string here
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

$isExternalEmp = [28, 30];

if (in_array($external, $isExternalEmp)) {
    require_once "group_wise/external_emp_incentive.php";
    exit;
} else if ($group == 1) {
    require_once "group_wise/group_1_incentive.php";
    exit;
} else if ($group == 2) {
    require_once "group_wise/group_2_incentive.php";
    exit;
} else {
    echo json_encode([
        "status"  => false,
        "message" => "User group id or employee type is missing."
    ]);
    exit;
}