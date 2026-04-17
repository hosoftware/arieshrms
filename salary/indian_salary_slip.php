<?php
//ini_set('display_errors', '1');
ob_start(); // Buffer any accidental output so FPDF can send headers cleanly

require_once('../connect.inc.php');
require_once('vendor/autoload.php');
//require_once('fpdf_protection.php');
ini_set('display_errors', '0');


    $month = 2;
    $year = 2026;
     $emp_id = $_GET['emp_id'];
 
$date = $year.'-'.$month.'-01';
$currentMonth = date('F',strtotime($date));
$days = getDays($year, $month);

 $sql = " SELECT  s.id as id,e.esi_no,e.pf_no,e.pan_no,e.wff_no,if(e.DOJ='0000-00-00','',DATE_FORMAT(e.DOJ,'%d/%m/%Y')) as DOJ,e.employee_code,e.id as employee_id,e.Name,d.full_name as company_name,d.address,d.po_box,d.city,d.telephone,d.fax,d.email as com_email,e.company_id, e.Designation, e.Email, s.*
	     FROM 0_cochin_salary s left join 0_emp e on e.id=s.emp_id left join 0_dimensions d on d.id=e.company_id 
             where s.month= '$month' AND s.year= '$year' AND s.emp_id =".$emp_id;

//display_error($sql);exit;

$trans_result = $mysqli->query($sql);

$row =  $trans_result->fetch_assoc();

$otherAddn = $row['Addition1']+$row['Addition2'];
$othrDed = $row['ded1']+$row['ded2']+$row['ded3']+$row['ded4']+$row['ded5'];
$toErn = $row['gross_salary']+$otherAddn;


$pdf=new FPDF();
$pdf->AddPage();
$pdf->SetLineWidth(.1);
//$pdf->SetFillColor('RED');
$pdf->SetFont('Times','B',18);
/*if($row['branch']==2)
{
    $pdf->image('../reporting/inner_logo.jpg', 14, 17, 50);  
}
else {*/
 $pdf->image('images/aries_logo.jpg', 14, 17, 50);   
//}

$pdf->image('images/Effismnew.jpg', 165, 14, null, 15);
$pdf->Cell(190,15,'',0,0,'C');
$pdf->Ln();

//Centered text in a framed 20*10 mm cell and line break
$pdf->SetXY(80, 27);
$pdf->Cell(60,10,'Wage Slip ',0,0,'C');
$pdf->SetFont('Arial','B',12);
$pdf->SetFont('Times','B',11);
// heading
$pdf->SetXY(15, 215);
$pdf->Rect(15,40,180,20);
$pdf->SetXY(75, 40);
/*if($row['branch']==2)
{
$pdf->Cell(50,10,'EPICA STUDIO PVT LTD',0,0,'C');
$pdf->SetXY(75, 45);
$pdf->Cell(50,10,'GANGA 110-S,PHASE - III, TECHNOPARK, THIRUVANANTHAPURAM, KERALA, INDIA',0,0,'C');
$pdf->SetXY(75, 50);
$pdf->Cell(50,10,'Tel : +91 471 2710710 Web : www.ariesepica.com Web : http://www.ariesgroupglobal.com',0,0,'C');
   
}
else
{*/
$pdf->Cell(50,10,$row['company_name'],0,0,'C');
$pdf->SetXY(75, 45);

$address .= $row['address'];

//$address .= trim(',',$address);
//print $address;exit;
//$pdf->Cell(50,10,$address,0,0,'C');

if(empty($address))
{
 $pdf->SetFont('Times','B',10);
$pdf->SetXY(17, 50);
$pdf->Cell(50,10,'BCG TOWER, OPP CSEZ, SEAPORT AIRPORT ROAD, KAKKANAD COCHIN 682037 PH: 0484 - 4081555 ',0,0,'L');   
}
else
{
    $address .=', '.$row['po_box'];
$address .=', '.$row['city'];
   $pdf->SetFont('Times','B',10);
//$pdf->SetXY(40, 50);
   $pdf->SetXY(50, 50);
$pdf->Cell(50,10,$address,0,0,'C'); 
}


   
   
/*if(!empty($row['telephone']))
{
   $pdf->SetXY(35, 50);
//$pdf->Cell(50,10,'PH: '.$row['telephone'].', FAX: '.$row['fax'].', Email : '.$row['com_email'],0,0,'C');
$pdf->Cell(5,10,'PH: '.$row['telephone'],0,0,'L'); 
}
if(!empty($row['fax']))
{
$pdf->SetXY(55, 50);
$pdf->Cell(80,10,'FAX: '.$row['fax'],0,0,'C');
}
if(!empty($row['com_email']))
{
$pdf->Cell(25,10,'Email : '.$row['com_email'],0,0,'C');
}*/
//}
$pdf->SetFont('Times','B',11);
$pdf->SetXY(20, 65);
$pdf->Cell(30,5,'Employee Name',0,0,'L');
$pdf->SetXY(57, 65);
$pdf->Cell(37,5,': '.$row['Name'],0,0,'L');

$pdf->SetXY(20, 72);
$pdf->Cell(30,5,'Employee Code',0,0,'L');
$pdf->SetXY(57, 72);
$pdf->Cell(37,5,': '.$row['employee_code'],0,0,'L');

$pdf->SetXY(20, 79);
$pdf->Cell(30,5,'Period of Payment',0,0,'L');
$pdf->SetXY(57, 79);
$pdf->Cell(37,5,': '.$currentMonth.' '.$year,0,0,'L');

$pdf->SetXY(20, 86);
$pdf->Cell(30,5,'Designation',0,0,'L');
$pdf->SetXY(57, 86);
$pdf->Cell(37,5,': '.$row['Designation'],0,0,'L');


$pdf->SetXY(20, 93);
$pdf->Cell(30,5,'Date of joining',0,0,'L');
$pdf->SetXY(57, 93);
$pdf->Cell(37,5,': '.$row['DOJ'],0,0,'L');

if($row['is_single_salary']==1)
{
    $total_attendance=round($row['NOWD'],2);
}
else
{
    $total_attendance=$row['total_working_days']-$row['lop_days'];
}

$pdf->SetXY(20, 100);
$pdf->Cell(30,5,'No. of days worked ',0,0,'L');
$pdf->SetXY(57, 100);
$pdf->Cell(37,5,': '.$total_attendance,0,0,'L');





$pdf->SetXY(140, 65);
$pdf->Cell(30,5,'Account No',0,0,'L');
$pdf->SetXY(162, 65);
$pdf->Cell(37,5,': '.$row['acc_no'],0,0,'L');


if(!empty($row['pf_no']))
{    
$pdf->SetXY(140, 72);
$pdf->Cell(30,5,'UAN',0,0,'L');
$pdf->SetXY(162, 72);
$pdf->Cell(37,5,': '.$row['pf_no'],0,0,'L');
}
if(!empty($row['esi_no']))
{$pdf->SetXY(140, 79);
$pdf->Cell(30,5,'IP Number',0,0,'L');
$pdf->SetXY(162, 79);
$pdf->Cell(37,5,': '.$row['esi_no'],0,0,'L');
}



//$pdf->SetXY(15, 215);
//$pdf->Rect(15,60,180,35);

/*$pdf->SetXY(20, 100);
$pdf->Cell(30,5,'Total Days:',0,0,'L');
$pdf->SetXY(65, 100);
$pdf->Cell(37,5,$row['total_working_days'],0,0,'R');

$pdf->SetXY(20, 105);
$pdf->Cell(30,5,'LOP Days:',0,0,'L');
$pdf->SetXY(65, 105);
$pdf->Cell(37,5,$row['lop_days'],0,0,'R');


if($month=='01')
{
$lastYr = $year; 
$lastMn = $month;
}
elseif($month=='02')
{
$lastYr = date('Y',strtotime('-1 month',strtotime($year))); 
$lastMn = date('m',strtotime('-1 month',strtotime($month))); 
}
elseif($month=='03')
{
$lastYr = date('Y',strtotime('-2 month',strtotime($year))); 
$lastMn = date('m',strtotime('-2 month',strtotime($month)));  
}
else
{
$lastYr = date('Y',strtotime('-3 month',strtotime($year))); 
$lastMn = date('m',strtotime('-3 month',strtotime($month)));    
}
$clBal = "SELECT sum(CL_total) as clT FROM `0_branch_div_leave_details` where employee_id='".$row['employee_id']."' and year>='$lastYr' and month>='$lastMn'";
$res_cl = mysql_query($clBal);
$rw_cl = mysql_fetch_assoc($res_cl);
if($rw_cl['clT']>=3)  
{
    $clBals = 0;
}
else
{
   $clBals = 3- $rw_cl['clT'];
}
$coffBal = "SELECT LC_total FROM `0_branch_div_leave_details` where employee_id='".$row['employee_id']."' and year='$year' and month='$month'";
$res_cff = mysql_query($coffBal);
$rw_cff = mysql_fetch_assoc($res_cff);

$pdf->SetXY(20, 110);
$pdf->Cell(30,5,'CL Balance:',0,0,'L');
$pdf->SetXY(65, 110);
$pdf->Cell(37,5,$clBals,0,0,'R');

$pdf->SetXY(15, 215);
$pdf->Rect(15,95,180,25);*/



$pdf->SetXY(20, 115);
$pdf->Cell(30,5,'Wages & Allowances',0,0,'L');
$pdf->SetXY(65, 125);
// wages and allowaance box
$pdf->SetXY(15, 215);
$pdf->Rect(15,110,100,15);

$pdf->SetXY(20, 130);
$pdf->Cell(30,5,'Particulars',0,0,'L');
$pdf->SetXY(65, 125);

// particluars  box
$pdf->SetXY(15, 215);
$pdf->Rect(15,125,100,10);

$pdf->Line(64,125,64,222);//line between payable
$pdf->Line(89,125,89,222);// line between paid

$pdf->SetXY(65, 130);
$pdf->Cell(60,5,'Payable',0,0,'L');
$pdf->SetXY(65, 125);

$pdf->SetXY(95, 130);
$pdf->Cell(85,5,'Paid',0,0,'L');
$pdf->SetXY(65, 125);

// content box
$pdf->SetXY(15, 240);
$pdf->Rect(15,135,100,87);// basic ,conveyance coloumn

// deduction box
$pdf->SetXY(105, 215);
$pdf->Rect(115,110,80,15);//deduction heading box

$pdf->SetXY(120, 115);
$pdf->Cell(30,5,'Deductions',0,0,'L');
$pdf->SetXY(153, 125);



$pdf->SetXY(120, 130);
$pdf->Cell(30,5,'Particulars',0,0,'L');
$pdf->SetXY(153, 125);

// particluars  box
$pdf->SetXY(105, 215);
$pdf->Rect(115,125,80,107);//box of particulars and amount

$pdf->Line(160,125,160,231.5);// line in bw particluars and amount

$pdf->SetXY(175, 130);
$pdf->Cell(165,5,'Amount',0,0,'L');
$pdf->SetXY(65, 125);

//deduction content box
$pdf->SetXY(105, 230);
$pdf->Rect(115,135,80,87);//inside ox of pf to otherdeductions



$pdf->SetXY(20, 140);
$pdf->Cell(30,5,'Basic',0,0,'L');
$pdf->SetXY(53, 140);
$pdf->Cell(30,5,number_format($row['basic_salary'],2),0,0,'R');

if( ($row['total_working_days'] ==  $row['lop_days']) ||  $row['s_basicsalary']>0){
    $basic_paid=$row['s_basicsalary'];
}
 else 
 {
    $basic_paid=$row['basic_salary'];
}

$pdf->SetXY(70, 140);
$pdf->Cell(37,5,number_format($basic_paid,2),0,0,'R');

if(($row['total_working_days'] ==  $row['lop_days']) || $row['s_hra']>0){
   $hra= $row['s_hra'];
}
 else 
 {
    $hra=$row['hra_salary'];
}

if(($row['total_working_days'] ==  $row['lop_days']) || $row['s_childeducation']>0){
    $child = $row['s_childeducation'];
 }
  else 
  {
    $child =$row['Child_education'];
 }

 if(($row['total_working_days'] ==  $row['lop_days']) || $row['s_dearnessallowance']>0){
    $dearness = $row['s_dearnessallowance'];
 }
  else 
  {
    $dearness = $row['dearness_allowance'];
 }

 if(($row['total_working_days'] ==  $row['lop_days']) || $row['s_specialallowance4']>0){
    $special4 = $row['s_specialallowance4'];
 }
  else 
  {
    $special4  = $row['special_allowance4'];
 }

 if(($row['total_working_days'] ==  $row['lop_days']) || $row['s_parentallowance']>0){
    $parent = $row['s_parentallowance'];
 }
  else 
  {
    $parent  = $row['parent_allowance'];
 }

 if(($row['total_working_days'] ==  $row['lop_days']) || $row['s_specialallowance']>0){
    $special = $row['s_specialallowance'];
 }
  else 
  {
    $special  = $row['special_allowance'];
 }

 

 if(($row['total_working_days'] ==  $row['lop_days']) || $row['s_specialallowance3']>0){
    $special3 = $row['s_specialallowance3'];
 }
  else 
  {
    $special3  = $row['special_allowance3'];
 }

 if(($row['total_working_days'] ==  $row['lop_days']) || $row['s_conveyance']>0){
    $conveyance=$row['s_conveyance'];
}
 else {
    $conveyance=$row['conveyance'];
}

$pdf->SetXY(20, 147.5);
$pdf->Cell(30,5,'House Rental Allowance',0,0,'L');
$pdf->SetXY(53, 147.5);
$pdf->Cell(30,5,number_format($row['hra_salary'],2),0,0,'R');

$pdf->SetXY(70, 147.5);
$pdf->Cell(37,5,number_format($hra,2),0,0,'R');



$pdf->SetXY(20, 155);
$pdf->Cell(30,5,'Conveyance Allowance',0,0,'L');
$pdf->SetXY(53, 155);
$pdf->Cell(30,5,number_format($row['conveyance'],2),0,0,'R');

$pdf->SetXY(70, 155);
$pdf->Cell(37,5,number_format($conveyance,2),0,0,'R');


$pdf->SetXY(20, 162.5);
$pdf->Cell(30,5,'Child Education',0,0,'L');
$pdf->SetXY(53, 162.5);
$pdf->Cell(30,5,number_format($row['Child_education'],2),0,0,'R');

$pdf->SetXY(70, 162.5);
$pdf->Cell(37,5,number_format($child,2),0,0,'R');


$pdf->SetXY(20, 170);
$pdf->Cell(30,5,'Target Allowance',0,0,'L');
$pdf->SetXY(50, 170);
$pdf->Cell(33,5,number_format($row['target_allowance'],2),0,0,'R');
$pdf->SetXY(70, 170);
$pdf->Cell(37,5,number_format($row['TA_paid'],2),0,0,'R');


$pdf->SetXY(20, 177.5);
$pdf->Cell(30,5,'Dearness Allowance',0,0,'L');
$pdf->SetXY(53, 177.5);
$pdf->Cell(30,5,number_format($row['dearness_allowance'],2),0,0,'R');
$pdf->SetXY(70, 177.5);
$pdf->Cell(37,5,number_format($dearness,2),0,0,'R');

$pdf->SetXY(20, 185);
$pdf->Cell(30,5,'Baby Allowance',0,0,'L');
$pdf->SetXY(53, 185);
$pdf->Cell(30,5,number_format($row['special_allowance4'],2),0,0,'R');
$pdf->SetXY(70, 185);
$pdf->Cell(37,5,number_format($special4,2),0,0,'R');

$pdf->SetXY(20, 192.5);
$pdf->Cell(30,5,'Parental Allowance',0,0,'L');
$pdf->SetXY(53, 192.5);
$pdf->Cell(30,5,number_format($row['parent_allowance'],2),0,0,'R');
$pdf->SetXY(70, 192.5);
$pdf->Cell(37,5,number_format($parent,2),0,0,'R');

$pdf->SetXY(20, 200);
$pdf->Cell(30,5,'Special Allowance2',0,0,'L');
$pdf->SetXY(53, 200);
$pdf->Cell(30,5,number_format($row['special_allowance'],2),0,0,'R');
$pdf->SetXY(70, 200);
$pdf->Cell(37,5,number_format($special),0,0,'R');

$pdf->SetXY(20, 207.5);
$pdf->Cell(30,5,'Special Allowance3',0,0,'L');
$pdf->SetXY(53, 207.5);
$pdf->Cell(30,5,number_format($row['special_allowance3'],2),0,0,'R');
$pdf->SetXY(70, 207.5);
$pdf->Cell(37,5,number_format($special3,2),0,0,'R');

$additions=$row['Addition2']+$row['Addition1'];
$pdf->SetXY(20, 215);
$pdf->Cell(30,5,'Additions',0,0,'L');

$pdf->SetXY(70, 215);
$pdf->Cell(37,5,number_format($additions,2),0,0,'R');





/*$pdf->SetXY(20, 185);
$pdf->Cell(30,5,'GROSS SALARY:',0,0,'L');
$pdf->SetXY(65, 185);
$pdf->Cell(37,5,number_format($row['gross_salary'],2),0,0,'R');*/


$pdf->SetXY(120, 140);
$pdf->Cell(30,5,'EPF',0,0,'L');
$pdf->SetXY(153, 140);
$pdf->Cell(37,5,number_format($row['PF_amount'],2),0,0,'R');


$pdf->SetXY(120, 147.5);
$pdf->Cell(30,5,'ESI',0,0,'L');
$pdf->SetXY(153, 147.5);
$pdf->Cell(37,5,number_format($row['ESI_amount'],2),0,0,'R');

$pdf->SetXY(120, 155);
$pdf->Cell(30,5,'Welfare Fund',0,0,'L');
$pdf->SetXY(153, 155);
$pdf->Cell(37,5,number_format($row['emp_WF'],2),0,0,'R');

$pdf->SetXY(120, 162.5);
$pdf->Cell(30,5,'TDS',0,0,'L');
$pdf->SetXY(153, 162.5);
$pdf->Cell(37,5,number_format($row['TDS_deductions'],2),0,0,'R');


$pdf->SetXY(120, 170);
$pdf->Cell(30,5,'Professional Tax',0,0,'L');
$pdf->SetXY(153, 170);
$pdf->Cell(37,5,number_format($row['Proffesional_tax'],2),0,0,'R');


$pdf->SetXY(120, 177.5);
$pdf->Cell(30,5,'Advance Repayment',0,0,'L');
$pdf->SetXY(153, 177.5);
$pdf->Cell(37,5,number_format($row['Advance'],2),0,0,'R');


$pdf->SetXY(120, 185);
$pdf->Cell(30,5,'Other Deductions',0,0,'L');
$pdf->SetXY(153, 185);
$pdf->Cell(37,5,number_format($othrDed,2),0,0,'R');

// $pdf->SetXY(120, 192.5);
// $pdf->Cell(30,5,'LOP Amount',0,0,'L');
// $pdf->SetXY(153, 192.5);
// $pdf->Cell(37,5,number_format($row['lop_amount'],2),0,0,'R');


/*$pdf->SetXY(120, 185);
$pdf->Cell(30,5,'Welfare Fund',0,0,'L');
$pdf->SetXY(153, 185);
$pdf->Cell(37,5,number_format($row['emp_WF'],2),0,0,'R');

/*$pdf->SetXY(110, 165);
$pdf->Cell(30,5,'Mobile Deductions:',0,0,'L');
$pdf->SetXY(153, 165);
$pdf->Cell(37,5,number_format($row['Proffesional_tax'],2),0,0,'R');

$pdf->SetXY(110, 170);
$pdf->Cell(30,5,'Other Dedudctions:',0,0,'L');
$pdf->SetXY(153, 170);
$pdf->Cell(37,5,number_format($othrDed,2),0,0,'R');*/


$total_deduction= $row['emp_WF']+$row['ESI_amount']+$row['PF_amount']+$row['ded1']+$row['ded2']+$row['ded3']+$row['ded4']+$row['ded5']+$row['TDS_deductions']+$row['Proffesional_tax']+$row['Advance'];

$pdf->SetXY(120, 222.5);
$pdf->Cell(30,5,'Total Deductions',0,0,'L');
$pdf->SetXY(153, 223);
$pdf->Cell(37,5,number_format($total_deduction,2),0,0,'R');


$pdf->SetXY(20, 222.5);
$pdf->Cell(30, 5, 'Gross Monthly', 0, 0, 'L');
$pdf->Ln(); // Move to the next line
$pdf->SetX(20); // Set X-coordinate back to the starting position
$pdf->Cell(30, 5, 'Emoluments', 0, 0, 'L');
$pdf->SetXY(50, 223);
$pdf->Cell(37,5,number_format($row['gross_salary'],2),0,0,'R');

$gross_paid=$basic_paid+$hra+$conveyance+$row['TA_paid']+$parent+$special+$child+$special3+$special4+$row['special_allowance5']+$dearness+$otherAddn ;
$pdf->SetXY(73, 223);
$pdf->Cell(37,5,number_format($gross_paid,2),0,0,'R');

$pdf->SetXY(105, 230);
$pdf->Rect(15,222,100,10);//gross month box

$pdf->SetXY(105, 230);
$pdf->Rect(115,222,80,10);//total deduction


//net wage paid
$pdf->SetXY(15, 230);
$pdf->Rect(15,232,180,15);
$netsalary=$gross_paid-$total_deduction;
$pdf->SetXY(65, 235);
$pdf->Cell(50,4,'Net Wage Paid - Rs. '.number_format($netsalary,2),0,0,'R');
//$pdf->SetXY(65, 215);
//$pdf->Cell(50,5,'Net Wage Paid - Rs. '.number_format($netsalary,2),0,0,'R');

$pdf->SetXY(20, 250);
$pdf->Cell(30,5,'This is a computer generated slip and does not require signature.',0,0,'L');

$pdf->SetXY(20, 300);
 if($otherAddn>0 || $row['total_deduction']>0 || $row['lop_amount']>0 ) {
     
     

$pdf->AddPage();
$pdf->SetLineWidth(.1);
//$pdf->SetFillColor('RED');
$pdf->SetFont('Times','B',18);

 $pdf->image('images/aries_logo.jpg', 14, 17, 50);   
//}

$pdf->image('images/Effismnew.jpg', 165, 14, null, 15);
$pdf->Cell(190,15,'',0,0,'C');
$pdf->Ln();

$pdf->SetXY(80, 27);
$pdf->Cell(60,10,'Wage Slip ',0,0,'C');
$pdf->SetFont('Arial','B',12);
$pdf->SetFont('Times','B',11);

$pdf->SetXY(15, 215);
$pdf->Rect(15,40,180,20);
$pdf->SetXY(75, 40);
/*if($row['branch']==2)
{
$pdf->Cell(50,10,'EPICA STUDIO PVT LTD',0,0,'C');
$pdf->SetXY(75, 45);
$pdf->Cell(50,10,'GANGA 110-S,PHASE - III, TECHNOPARK, THIRUVANANTHAPURAM, KERALA, INDIA',0,0,'C');
$pdf->SetXY(75, 50);
$pdf->Cell(50,10,'Tel : +91 471 2710710 Web : www.ariesepica.com Web : http://www.ariesgroupglobal.com',0,0,'C');
   
}
else
{*/
$pdf->Cell(50,10,$row['company_name'],0,0,'C');
$pdf->SetXY(75, 45);

//$pdf->Cell(50,10,$address,0,0,'C');
if(empty($address))
{
  $pdf->SetFont('Times','B',10);
$pdf->SetXY(17, 50);
$pdf->Cell(50,10,'BCG TOWER, OPP CSEZ, SEAPORT AIRPORT ROAD, KAKKANAD COCHIN 682037 PH: 0484 – 4081555 ',0,0,'L');  
}
else
{
    $pdf->SetFont('Times','B',10);
 $pdf->SetXY(50, 50);
$pdf->Cell(50,10,$address,0,0,'C'); 
}


   
   
/*if(!empty($row['telephone']))
{
   $pdf->SetXY(35, 50);
//$pdf->Cell(50,10,'PH: '.$row['telephone'].', FAX: '.$row['fax'].', Email : '.$row['com_email'],0,0,'C');
$pdf->Cell(5,10,'PH: '.$row['telephone'],0,0,'L'); 
}
if(!empty($row['fax']))
{
$pdf->SetXY(55, 50);
$pdf->Cell(80,10,'FAX: '.$row['fax'],0,0,'C');
}
if(!empty($row['com_email']))
{
$pdf->Cell(25,10,'Email : '.$row['com_email'],0,0,'C');
}*/
//}
$pdf->SetFont('Times','B',11);
$pdf->SetXY(20, 65);
$pdf->Cell(30,5,'Employee Name',0,0,'L');
$pdf->SetXY(57, 65);
$pdf->Cell(37,5,': '.$row['Name'],0,0,'L');

$pdf->SetXY(20, 72);
$pdf->Cell(30,5,'Employee Code',0,0,'L');
$pdf->SetXY(57, 72);
$pdf->Cell(37,5,': '.$row['employee_code'],0,0,'L');

$pdf->SetXY(20, 79);
$pdf->Cell(30,5,'Period of Payment',0,0,'L');
$pdf->SetXY(57, 79);
$pdf->Cell(37,5,': '.$currentMonth.' '.$year,0,0,'L');

$pdf->SetXY(20, 86);
$pdf->Cell(30,5,'Designation',0,0,'L');
$pdf->SetXY(57, 86);
$pdf->Cell(37,5,': '.$row['Designation'],0,0,'L');


$pdf->SetXY(20, 93);
$pdf->Cell(30,5,'Date of joining',0,0,'L');
$pdf->SetXY(57, 93);
$pdf->Cell(37,5,': '.$row['DOJ'],0,0,'L');

if($row['is_single_salary']==1)
{
    $total_attendance=round($row['NOWD'],2);
}
else
{
    $total_attendance=$row['total_working_days']-$row['lop_days'];
}

$pdf->SetXY(20, 100);
$pdf->Cell(30,5,'No. of days worked ',0,0,'L');
$pdf->SetXY(57, 100);
$pdf->Cell(37,5,': '.$total_attendance,0,0,'L');





$pdf->SetXY(140, 65);
$pdf->Cell(30,5,'Account No',0,0,'L');
$pdf->SetXY(162, 65);
$pdf->Cell(37,5,': '.$row['acc_no'],0,0,'L');


if(!empty($row['pf_no']))
{    
$pdf->SetXY(140, 72);
$pdf->Cell(30,5,'UAN',0,0,'L');
$pdf->SetXY(162, 72);
$pdf->Cell(37,5,': '.$row['pf_no'],0,0,'L');
}
if(!empty($row['esi_no']))
{$pdf->SetXY(140, 79);
$pdf->Cell(30,5,'IP Number',0,0,'L');
$pdf->SetXY(162, 79);
$pdf->Cell(37,5,': '.$row['esi_no'],0,0,'L');
}



$y=130;
$pdf->SetFont('Times','B',15);
$pdf->SetXY(20, 115);
$pdf->Cell(30,5,'Deductions/Additions',0,0,'L');
$pdf->SetXY(65, 125);

//Additions/deduction box
$pdf->SetXY(15, 215);
$pdf->Rect(20,110,180,15);

$pdf->SetFont('Times','B',13);
if($row['total_deduction']>0)
{
    

 $pdf->SetXY(20, $y);
$pdf->Cell(30,5,'Deductions',0,0,'L');
//$pdf->SetXY(65, 125);   
 $pdf->SetFont('Times','B',11);   
if($row['PF_amount'] >0)
{
   
//$rect_y = $rect_y + $h + 1;
    $y = $y + 6;
$pdf->SetXY(20, $y);
$pdf->Cell(40,5,'EPF',0,0,'L');
$pdf->SetXY(125, $y);
$pdf->Cell(37,5,number_format($row['PF_amount'],2),0,0,'R');
}
if($row['ESI_amount'] >0)
{
    $y = $y + 6;
$pdf->SetXY(20, $y);
$pdf->Cell(40,5,'ESI',0,0,'L');
$pdf->SetXY(125, $y);
$pdf->Cell(37,5,number_format($row['ESI_amount'],2),0,0,'R');
}
if($row['emp_WF'] >0)
{
    $y = $y + 6;
$pdf->SetXY(20, $y);
$pdf->Cell(40,5,'Welfare Fund',0,0,'L');
$pdf->SetXY(125, $y);
$pdf->Cell(37,5,number_format($row['emp_WF'],2),0,0,'R');
}
 if($row['TDS_deductions'] >0)
{   
     $y = $y + 6;
 $pdf->SetXY(20, $y);
$pdf->Cell(40,5,'TDS',0,0,'L');
$pdf->SetXY(125, $y);
$pdf->Cell(37,5,number_format($row['TDS_deductions'],2),0,0,'R');
}
 if($row['Proffesional_tax'] >0)
{   
     $y = $y + 6;
 $pdf->SetXY(20, $y);
$pdf->Cell(4,05,'Professional Tax',0,0,'L');
$pdf->SetXY(125, $y);
$pdf->Cell(37,5,number_format($row['Proffesional_tax'],2),0,0,'R');
}
 if($row['Advance'] >0)
{   
     $y = $y + 6;
 $pdf->SetXY(20, $y);
$pdf->Cell(40,5,'Advance Repayment',0,0,'L');
$pdf->SetXY(125, $y);
$pdf->Cell(37,5,number_format($row['Advance'],2),0,0,'R');
}
 if($row['ded1'] >0)
{   
     $y = $y + 6;
 $pdf->SetXY(20, $y);
$pdf->Cell(40,5,$row['ded1_remarks'],0,0,'L');
$pdf->SetXY(125, $y);
$pdf->Cell(37,5,number_format($row['ded1'],2),0,0,'R');
}
 if($row['ded2'] >0)
{   
     $y = $y + 6;
 $pdf->SetXY(20, $y);
$pdf->Cell(40,5,$row['ded2_remarks'],0,0,'L');
$pdf->SetXY(125, $y);
$pdf->Cell(37,5,number_format($row['ded2'],2),0,0,'R');
}
 if($row['ded3'] >0)
{   
     $y = $y + 6;
 $pdf->SetXY(20, $y);
$pdf->Cell(40,5,$row['ded3_remarks'],0,0,'L');
$pdf->SetXY(125, $y);
$pdf->Cell(37,5,number_format($row['ded3'],2),0,0,'R');
}
 if($row['ded4'] >0)
{   
     $y = $y + 6;
 $pdf->SetXY(20, $y);
$pdf->Cell(40,5,$row['ded4_remarks'],0,0,'L');
$pdf->SetXY(125, $y);
$pdf->Cell(37,5,number_format($row['ded4'],2),0,0,'R');
}
 if($row['ded5'] >0)
{   
     $y = $y + 6;
 $pdf->SetXY(20, $y);
$pdf->Cell(40,5,$row['ded5_remarks'],0,0,'L');
$pdf->SetXY(125, $y);
$pdf->Cell(37,5,number_format($row['ded5'],2),0,0,'R');
}
$y = $y + 6;
$pdf->SetXY(40, $y);
$pdf->Cell(40,5,'Total',0,0,'L');
$pdf->SetXY(125, $y);
$pdf->Cell(37,5,'- '.number_format($total_deduction,2),0,0,'R');
$y = $y + 6;
}
if($otherAddn >0)
{
    $pdf->SetFont('Times','B',13);
$pdf->SetXY(20, $y);
$pdf->Cell(30,5,'Additions',0,0,'L');

$pdf->SetFont('Times','B',11);
if($row['Addition1'] >0)
{
   
//$rect_y = $rect_y + $h + 1;
 $y = $y + 6;
$pdf->SetXY(20, $y);
$pdf->Cell(40,5,'Addition1',0,0,'L');
$pdf->SetXY(125, $y);
$pdf->Cell(37,5,number_format($row['Addition1'],2),0,0,'R');
}
if($row['Addition2'] >0)
{
    $y = $y + 6;
$pdf->SetXY(20, $y);
$pdf->Cell(40,5,'Addition2',0,0,'L');
$pdf->SetXY(125, $y);
$pdf->Cell(37,5,number_format($row['Addition2'],2),0,0,'R');
}
$y = $y + 6;
$pdf->SetXY(40, $y);
$pdf->Cell(50,5,'Total',0,0,'L');
$pdf->SetXY(125, $y);
$pdf->Cell(37,5,'+ '.number_format($otherAddn,2),0,0,'R');

}


if($row['lop_amount'] > 0) {
    $y = $y + 6; // move down
    $pdf->SetFont('Times','B',11);
    $pdf->SetXY(20, $y);
    $pdf->Cell(40,5,'LOP Amount',0,0,'L');
    $pdf->SetXY(125, $y);
    $pdf->Cell(37,5,number_format($row['lop_amount'],2),0,0,'R');
}
 
// Final note
$y = $y + 10;


$pdf->SetXY(20, 250);
$pdf->Cell(30,5,'This is a computer generated slip and does not require signature.',0,0,'L');
 }

//$pdf->SetFillColor(255,0,0);
$pdf->SetDisplayMode('real');
$pdf->Output("Salary_Slip_".$row['Name']."_".$currentMonth."_".$year.".pdf", "D");



function getDays($year, $month) {
    $num_of_days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    return $num_of_days;
}

?> 