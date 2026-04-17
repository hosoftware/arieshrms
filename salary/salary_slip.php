<?php

ob_start();

require_once('../connect.inc.php');
require_once('vendor/autoload.php');
require_once('fpdf_protection.php');

$month          = trim($_REQUEST['month'] ?? '');
$year           = !empty($_REQUEST['year']) ? trim($_REQUEST['year']) : date('Y');
$user_id        = trim($_REQUEST['user_id'] ?? '');

if($user_id==''){
    echo "Error: Bad request";
    exit;
}

$grp = $mysqli->prepare("SELECT emp_type, group_id FROM tbl_users WHERE user_id = ?");
$grp->bind_param("i", $user_id);
$grp->execute();
$grpResult = $grp->get_result();
$row = $grpResult->fetch_assoc();
$group = $row['group_id'] ?? null;
$external = $row['emp_type'] ?? null;
$grp->close();

class PDF extends FPDF_Protection
{
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,'Page '. $this->PageNo() . "/{nb}".$this->AliasNbPages(),0,0,'C');
    }
}

$isExternalEmp = [28, 30];

if(in_array($external, $isExternalEmp))
{
    require_once "group_wise/external_emp_slip.php";
    exit;
}
else if($group==1)
{
    require_once "group_wise/group_1_slip.php";
    exit;
}
else if($group==2)
{
    require_once "group_wise/group_2_slip.php";
    exit;
}
else
{
    echo json_encode([
        "status" => false,
        "message"  => "User group id or employee type is missing."
    ]);    
    exit;   
}
exit;