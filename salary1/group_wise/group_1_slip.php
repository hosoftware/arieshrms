<?php

$show_deduction = intval($_REQUEST['show_deduction'] ?? 0); // fix: read from request
$h              = 6; 

$sql="SELECT
ROUND(s.EBS+s.EFA+s.EHRA+s.EFOT+s.EMA+s.ECA+s.EPA+s.EFI+s.ECE+s.ETA+s.ETRA+s.EOA+s.EPAR+s.ESA+s.ESA3) AS net_salary_no_decuction,
d2.name AS division,
d3.name AS sub_division,
e.id,
e.Name,
e.sub_division_id,
s.company_id,
e.Designation,
e.employee_code,
YEAR(u.dob) AS dob_year,
s.*
FROM `0_salary` s
LEFT JOIN `0_emp` e ON e.id = s.emp_id
LEFT JOIN `0_dimensions` d2 ON d2.id = e.division_id
LEFT JOIN `0_dimensions` d3 ON d3.id = e.sub_division_id
LEFT JOIN `tbl_users` u ON u.hr_id = s.emp_id
WHERE s.month = '$month' AND s.year = '$year' AND u.user_id = $user_id";

$trans_result = $mysqli->query($sql);

$date = $year."-".$month."-16";

// -------------------------------------------------------
// Extend FPDF_Protection to add a custom footer
// -------------------------------------------------------


// class PDF extends FPDF_Protection
// {
//     function Footer()
//     {
//         // Go to 1.5 cm from bottom
//         $this->SetY(-15);
//         // Select Arial italic 8
//         $this->SetFont('Arial','I',8);
//         // Print centered page number
//         $this->Cell(0,10,'Page '. $this->PageNo() . "/{nb}".$this->AliasNbPages(),0,0,'C');
//     }
// }



$pdf = new PDF();

// -------------------------------------------------------
// PASSWORD PROTECTION
// Options: 'print', 'modify', 'copy', 'annot-forms'
// User password   = required to OPEN the PDF
// Owner password  = required to change permissions
//
// Using employee_code as the user password so each
// employee opens their slip with their own code.
// Change 'owner_secure_pass_2024' to any strong owner password.
// -------------------------------------------------------

$trans_row = $trans_result->fetch_assoc();

// Check if any row was returned
if (!$trans_row) {
    ob_end_clean();
    echo "No data found for the selected month/year.";
    exit;
}

// We need trans_row before SetProtection so we can use employee_code as password
$user_password  = $trans_row['dob_year']; // e.g. "EMP001"
$owner_password = 'qwerty';    // Change this to your own secret

$pdf->SetProtection(
    ['print'],       // Only allow printing; remove 'print' to block that too
    $user_password,
    $owner_password
);

// -------------------------------------------------------
// Salary calculations
// -------------------------------------------------------
$net_sal = $trans_row['EBS'] + $trans_row['EFA'] + $trans_row['EHRA'] + $trans_row['EFOT'] + $trans_row['EMA'] +
            $trans_row['ECA'] + $trans_row['EPA'] + $trans_row['EFI'] + $trans_row['ECE'] + $trans_row['ETA'] +
            $trans_row['ETRA'] + $trans_row['EOA'] + $trans_row['EDA'] + $trans_row['EPAR'] + $trans_row['ESA'] +
            $trans_row['ESA3'] + $trans_row['ESA4'];

// -------------------------------------------------------
// PAGE 1 — Salary Summary
// -------------------------------------------------------
    $pdf->AddPage();
    $pdf->SetLineWidth(.5);

    $pdf->SetFont('Times','BU',14);

    $pdf->Cell(190,15,'',0,0,'C');
    $pdf->Ln();

    if (file_exists("images/company/".$trans_row['company_id'].".jpg")) {
        $pdf->image("images/company/".$trans_row['company_id'].".jpg",7,5,null,33);
    } else {
        $pdf->image("images/company/8.jpg",7,5,null,33);
    }

    $pdf->Rect(18,80,175,200);
    $pdf->SetXY(18,42);
    $pdf->Cell(175,10,'SALARY SHEET - '.strtoupper(date("F",strtotime($date)))." ".$year,0,0,'C');

    $pdf->SetFont('Times','B',12);

    $pdf->Rect(18,52,175,20);
    $pdf->SetXY(25,53);
    $pdf->Cell(40,6,'Employee Name:',0,0,'L');
    $pdf->Cell(120,6,$trans_row['Name'],0,0,'L');
    $pdf->SetXY(25,59);
    $pdf->Cell(40,6,'Employee Code:',0,0,'L');
    $pdf->Cell(120,6,$trans_row['employee_code'],0,0,'L');
    
    $pdf->SetXY(25,65);
    $pdf->Cell(40,6,'Designation:',0,0,'L');
    $pdf->Cell(120,6,$trans_row['designation'],0,0,'L');

    $pdf->SetFont('Times','',11);

    $pdf->SetXY(18,73);
    $pdf->Cell(25,7,'Division:',0,0,'L');
    $pdf->Cell(50,7,$trans_row['division'],0,0,'L');
    $pdf->Cell(25,7,'SubDivision:',0,0,'L');
    $pdf->Cell(50,7,$trans_row['sub_division'],0,0,'L');

    if ($trans_row['revision'] != 0) {
        $pdf->SetFont('Times','',11);
        $pdf->SetXY(170,73);
        $pdf->Cell(175,7,'Revision #'.$trans_row['revision'],0,0,'L');
    }

    // ---- Fixed Package (left column) ----
    $pdf->SetFont('Times','B',11);
    $pdf->SetLineWidth(.4);
    $pdf->SetXY(20,82);
    $pdf->Cell(40,7,'Fixed Package:',0,0,'L');

    $pdf->SetFont('Times','',11);

    $pdf->SetXY(20,89);
    $pdf->Cell(40,7,'Basic Salary',0,0,'L');
    $pdf->Cell(10,7,$trans_row['currency'],0,0,'C');
    $pdf->Cell(25,7,$trans_row['basic_salary'],0,0,'R');

    $pdf->SetXY(20,95);
    $pdf->Cell(40,7,"Food Allowance",0,0,'L');
    $pdf->Cell(10,7,$trans_row['currency'],0,0,'C');
    $pdf->Cell(25,7,$trans_row['food_allowance'],0,0,'R');

    $pdf->SetXY(20,101);
    $pdf->Cell(40,7,"House Allowance",0,0,'L');
    $pdf->Cell(10,7,$trans_row['currency'],0,0,'C');
    $pdf->Cell(25,7,$trans_row['house_allowance'],0,0,'R');

    $pdf->SetXY(20,107);
    $pdf->Cell(40,7,"Fixed Overtime",0,0,'L');
    $pdf->Cell(10,7,$trans_row['currency'],0,0,'C');
    $pdf->Cell(25,7,$trans_row['fixed_overtime'],0,0,'R');

    $pdf->SetXY(20,113);
    $pdf->Cell(40,7,"Mobile Allowance",0,0,'L');
    $pdf->Cell(10,7,$trans_row['currency'],0,0,'C');
    $pdf->Cell(25,7,$trans_row['mobile_allowance'],0,0,'R');

    $pdf->SetXY(20,119);
    $pdf->Cell(40,7,"Car Allowance",0,0,'L');
    $pdf->Cell(10,7,$trans_row['currency'],0,0,'C');
    $pdf->Cell(25,7,$trans_row['car_allowance'],0,0,'R');

    $pdf->SetXY(20,125);
    $pdf->Cell(40,7,"Petrol Allowance",0,0,'L');
    $pdf->Cell(10,7,$trans_row['currency'],0,0,'C');
    $pdf->Cell(25,7,$trans_row['petrol_allowance'],0,0,'R');

    $pdf->SetXY(20,131);
    $pdf->Cell(40,7,"Fixed Incentive",0,0,'L');
    $pdf->Cell(10,7,$trans_row['currency'],0,0,'C');
    $pdf->Cell(25,7,$trans_row['fixed_incentive'],0,0,'R');

    $pdf->SetXY(20,137);
    $pdf->Cell(40,7,"Child Education",0,0,'L');
    $pdf->Cell(10,7,$trans_row['currency'],0,0,'C');
    $pdf->Cell(25,7,$trans_row['child_education_allowance'],0,0,'R');

    $pdf->SetXY(20,143);
    $pdf->Cell(40,7,"Traveling Allowance",0,0,'L');
    $pdf->Cell(10,7,$trans_row['currency'],0,0,'C');
    $pdf->Cell(25,7,$trans_row['traveling_allowance'],0,0,'R');

    $pdf->SetXY(20,149);
    $pdf->Cell(40,7,"Target Allowance",0,0,'L');
    $pdf->Cell(10,7,$trans_row['currency'],0,0,'C');
    $pdf->Cell(25,7,$trans_row['target_allowance'],0,0,'R');

    $pdf->SetXY(20,155);
    $pdf->Cell(40,7,"Uniform Allowance",0,0,'L');
    $pdf->Cell(10,7,$trans_row['currency'],0,0,'C');
    $pdf->Cell(25,7,$trans_row['other_allowance'],0,0,'R');

    $pdf->SetXY(20,161);
    $pdf->Cell(40,7,"Baby Allowance",0,0,'L');
    $pdf->Cell(10,7,$trans_row['currency'],0,0,'C');
    $pdf->Cell(25,7,($trans_row['special_allowance4'] ?? '0.00'),0,0,'R');

    $pdf->SetXY(20,167);
    $pdf->Cell(40,7,"Dearness Allowance",0,0,'L');
    $pdf->Cell(10,7,$trans_row['currency'],0,0,'C');
    $pdf->Cell(25,7,($trans_row['dearness_Allowance'] ?? '0.00'),0,0,'R');

    $pdf->SetXY(20,173);
    $pdf->Cell(40,7,"Special Allowance 1",0,0,'L');
    $pdf->Cell(10,7,$trans_row['currency'],0,0,'C');
    $pdf->Cell(25,7,$trans_row['parent_allowance'],0,0,'R');

    $pdf->SetXY(20,179);
    $pdf->Cell(40,7,"Special Allowance 2",0,0,'L');
    $pdf->Cell(10,7,$trans_row['currency'],0,0,'C');
    $pdf->Cell(25,7,$trans_row['special_allowance'],0,0,'R');

    $pdf->SetXY(20,185);
    $pdf->Cell(40,7,"Special Allowance 3",0,0,'L');
    $pdf->Cell(10,7,$trans_row['currency'],0,0,'C');
    $pdf->Cell(25,7,$trans_row['special_allowance3'],0,0,'R');

    $pdf->SetLineWidth(.3);

    $pdf->SetXY(20,196);
    $pdf->SetFont('Times','B',13);
    $pdf->Cell(40,7,'Total',0,0,'L');
    $pdf->Cell(10,7,'',0,0,'C');
    $pdf->Cell(25,7,$trans_row['gross_salary'],0,0,'R');

    // ---- Actual Salary (right column) ----
    $pdf->SetLineWidth(.4);
    $pdf->SetFont('Times','',11);

    $pdf->SetXY(120,82);
    $pdf->Cell(50,7,'Number of Working Days',0,0,'L');
    $pdf->Cell(10,7,$trans_row['NOWD'],0,0,'R');

    $pdf->SetXY(120,89);
    $pdf->Cell(35,7,'Basic Salary',0,0,'L');
    $pdf->Cell(15,7,$trans_row['currency'],0,0,'C');
    $pdf->Cell(15,7,$trans_row['EBS'],0,0,'R');

    $pdf->SetXY(120,95);
    $pdf->Cell(35,7,"Food Allowance",0,0,'L');
    $pdf->Cell(15,7,$trans_row['currency'],0,0,'C');
    $pdf->Cell(15,7,$trans_row['EFA'],0,0,'R');

    $pdf->SetXY(120,101);
    $pdf->Cell(35,7,"House Allowance",0,0,'L');
    $pdf->Cell(15,7,$trans_row['currency'],0,0,'C');
    $pdf->Cell(15,7,$trans_row['EHRA'],0,0,'R');

    $pdf->SetXY(120,107);
    $pdf->Cell(35,7,"Fixed Overtime",0,0,'L');
    $pdf->Cell(15,7,$trans_row['currency'],0,0,'C');
    $pdf->Cell(15,7,$trans_row['EFOT'],0,0,'R');

    $pdf->SetXY(120,113);
    $pdf->Cell(35,7,"Mobile Allowance",0,0,'L');
    $pdf->Cell(15,7,$trans_row['currency'],0,0,'C');
    $pdf->Cell(15,7,$trans_row['EMA'],0,0,'R');

    $pdf->SetXY(120,119);
    $pdf->Cell(35,7,"Car Allowance",0,0,'L');
    $pdf->Cell(15,7,$trans_row['currency'],0,0,'C');
    $pdf->Cell(15,7,$trans_row['ECA'],0,0,'R');

    $pdf->SetXY(120,125);
    $pdf->Cell(35,7,"Petrol Allowance",0,0,'L');
    $pdf->Cell(15,7,$trans_row['currency'],0,0,'C');
    $pdf->Cell(15,7,$trans_row['EPA'],0,0,'R');

    $pdf->SetXY(120,131);
    $pdf->Cell(35,7,"Fixed Incentive",0,0,'L');
    $pdf->Cell(15,7,$trans_row['currency'],0,0,'C');
    $pdf->Cell(15,7,$trans_row['EFI'],0,0,'R');

    $pdf->SetXY(120,137);
    $pdf->Cell(35,7,"Child Education",0,0,'L');
    $pdf->Cell(15,7,$trans_row['currency'],0,0,'C');
    $pdf->Cell(15,7,$trans_row['ECE'],0,0,'R');

    $pdf->SetXY(120,143);
    $pdf->Cell(35,7,"Traveling Allowance",0,0,'L');
    $pdf->Cell(15,7,$trans_row['currency'],0,0,'C');
    $pdf->Cell(15,7,$trans_row['ETA'],0,0,'R');

    $pdf->SetXY(120,149);
    $pdf->Cell(35,7,"Target Allowance",0,0,'L');
    $pdf->Cell(15,7,$trans_row['currency'],0,0,'C');
    $pdf->Cell(15,7,$trans_row['ETRA'],0,0,'R');

    $pdf->SetXY(120,155);
    $pdf->Cell(35,7,"Uniform Allowance",0,0,'L');
    $pdf->Cell(15,7,$trans_row['currency'],0,0,'C');
    $pdf->Cell(15,7,$trans_row['EOA'],0,0,'R');

    $pdf->SetXY(120,161);
    $pdf->Cell(35,7,"Baby Allowance",0,0,'L');
    $pdf->Cell(15,7,$trans_row['currency'],0,0,'C');
    $pdf->Cell(15,7,($trans_row['ESA4'] ?? 0.00),0,0,'R');

    $pdf->SetXY(120,167);
    $pdf->Cell(35,7,"Dearness Allowance",0,0,'L');
    $pdf->Cell(15,7,$trans_row['currency'],0,0,'C');
    $pdf->Cell(15,7,($trans_row['EDA'] ?? 0.00),0,0,'R');

    $pdf->SetXY(120,173);
    $pdf->Cell(35,7,"Special Allowance 1",0,0,'L');
    $pdf->Cell(15,7,$trans_row['currency'],0,0,'C');
    $pdf->Cell(15,7,$trans_row['EPAR'],0,0,'R');

    $pdf->SetXY(120,179);
    $pdf->Cell(35,7,"Special Allowance 2",0,0,'L');
    $pdf->Cell(15,7,$trans_row['currency'],0,0,'C');
    $pdf->Cell(15,7,$trans_row['ESA'],0,0,'R');

    $pdf->SetXY(120,185);
    $pdf->Cell(35,7,"Special Allowance 3",0,0,'L');
    $pdf->Cell(15,7,$trans_row['currency'],0,0,'C');
    $pdf->Cell(15,7,$trans_row['ESA3'],0,0,'R');

    $pdf->SetFont('Times','',11);
    $pdf->SetLineWidth(.3);

    $pdf->SetXY(120,197);
    $pdf->SetFont('Times','B',13);
    $pdf->Cell(35,7,'Total',0,0,'L');
    $pdf->Cell(15,7,'',0,0,'R');
    $pdf->Cell(15,7,$net_sal,0,0,'R');

    // ---- Additions / Deductions summary box ----
    $pdf->SetXY(20,210);
    $pdf->SetFont('Times','B',11);
    $pdf->Cell(30,7,'Additions/Deductions',0,0,'L');

    $y       = 210 + 2;
    $rect_y  = 0;

    $pdf->SetFont('Times','',11);
    $height  = 0;
    $add1 = $add2 = $ded1 = $ded2 = $ded3 = $ded4 = $ded5 = $ded6 = $ded7 = $ded8 = $hra = $ticket = $advance = 0;

    $total_addition  = $trans_row['add1'] + $trans_row['add2'] + $trans_row['add3'];
    $total_deduction = $trans_row['ded1'] + $trans_row['ded2'] + $trans_row['ded3']
                     + $trans_row['ded4'] + $trans_row['ded5'] + $trans_row['ded6']
                     + $trans_row['ded7'] + $trans_row['ded8'] + $trans_row['hra_deduction']
                     + $trans_row['advance_deduction'];

    if ($total_addition > 0) {
        $height++;
    }

    $add   = 0;
    $ded   = 0;
    $total = 0;
    $rect_y = 25;
    $y      = 217;

    if ($total_addition > 0) {
        $rect_y = $rect_y + $h + 1;
        $pdf->SetXY(20, $y);
        $pdf->Cell(40, 7, "Addition", 0, 0, 'L');
        $pdf->SetXY(168, $y);
        $pdf->Cell(15, 7, '+ '.$total_addition, 0, 0, 'R');
        $y = $y + 7;
    }
    if ($total_deduction > 0) {
        $rect_y = $rect_y + $h + 1;
        $pdf->SetXY(20, $y);
        $pdf->Cell(40, 7, "Deduction", 0, 0, 'L');
        $pdf->SetXY(168, $y);
        $pdf->Cell(15, 7, '- '.$total_deduction, 0, 0, 'R');
        $y = $y + 5;
    }

    $pdf->SetFont('Times','B',11);

    $total = $total_addition - $total_deduction;

    $y = 230;
    $pdf->SetXY(145, $y);
    $pdf->Cell(10, 7, "Total", 0, 0, 'L');
    $pdf->SetXY(168, $y);
    $pdf->Cell(15, 7, $total, 0, 0, 'R');
    $rect_y = $rect_y + ($h ?? 0);

    $pdf->Rect(20, 210, 170, 28);

    $pdf->SetFont('Times','B',13);

    $y = 240;
    $pdf->SetXY(75, $y);
    $pdf->Cell(30, 7, "Net Salary", 0, 0, 'L');
    $pdf->Cell(10, 7, $trans_row['currency'], 0, 0, 'C');
    $pdf->Cell(25, 7, ceil($trans_row['net_salary']).'.00', 0, 0, 'R');

    $pdf->SetFont('Times','I',10);

    $y = 250;
    $pdf->SetXY(25, 250);
    $pdf->Cell(50, 7, "Date of Pay ".($trans_row['DAP']), 0, 0, 'L');

    $y = 257;
    $pdf->SetXY(25, $y);
    if ($trans_row['is_cash'] == 'true') {
        $pdf->Cell(50, 7, 'CASH PAID', 0, 0, 'L');
        $pdf->Cell(45, 7, '', 0, 0, 'L');
    } else {
        $pdf->Cell(50, 7, 'Transfered to: '.$trans_row['bank_name']." A/c: ".$trans_row['acc_no'], 0, 0, 'L');
        $y = 264;
        $pdf->SetXY(25, $y);
        $pdf->Cell(45, 7, "Iban No. ".$trans_row['iban_no'], 0, 0, 'L');
    }

// -------------------------------------------------------
// PAGE 2 — Detailed Additions & Deductions (if any)
// -------------------------------------------------------
    if ($total_addition > 0 || $total_deduction > 0) {

        $pdf->AddPage();
        $pdf->SetLineWidth(.5);

        $pdf->SetFont('Times','BU',14);

        $pdf->Cell(190,15,'',0,0,'C');
        $pdf->Ln();

        if (file_exists("images/company/".$trans_row['company_id'].".jpg")) {
            $pdf->image("images/company/".$trans_row['company_id'].".jpg",7,5,null,33);
        } else {
            $pdf->image("images/company/8.jpg",7,5,null,33);
        }

        $pdf->Rect(18,80,175,200);
        $pdf->SetXY(18,42);
        $pdf->Cell(175,10,'SALARY SHEET - '.strtoupper(date("F",strtotime($date)))." ".$year,0,0,'C');

        $pdf->SetFont('Times','B',12);

        $pdf->Rect(18,52,175,20);
        $pdf->SetXY(25,53);
        $pdf->Cell(40,6,'Employee Name:',0,0,'L');
        $pdf->Cell(120,6,$trans_row['Name'],0,0,'L');
        $pdf->SetXY(25,59);
        $pdf->Cell(40,6,'Employee Code:',0,0,'L');
        $pdf->Cell(120,6,$trans_row['employee_code'],0,0,'L');

        $pdf->SetXY(25,65);
        $pdf->Cell(40,6,'Designation:',0,0,'L');
        $pdf->Cell(120,6,$trans_row['designation'],0,0,'L');

        $pdf->SetFont('Times','',11);

        $pdf->SetXY(18,73);
        $pdf->Cell(25,7,'Division:',0,0,'L');
        $pdf->Cell(50,7,$trans_row['division'],0,0,'L');
        $pdf->Cell(25,7,'SubDivision:',0,0,'L');
        $pdf->Cell(50,7,$trans_row['sub_division'],0,0,'L');

        if ($trans_row['revision'] != 0) {
            $pdf->SetFont('Times','',11);
            $pdf->SetXY(170,73);
            $pdf->Cell(175,7,'Revision #'.$trans_row['revision'],0,0,'L');
        }

        $h = 6;
        $height = 0;
        $add1 = $add2 = $add3 = $ded1 = $ded2 = $ded3 = $ded4 = $ded5 = $ded6 = $ded7 = $ded8 = $hra = $ticket = $advance = 0;

        if ($trans_row['add1'] > 0) { $height++; $add1 = $trans_row['add1']; }
        if ($trans_row['add2'] > 0) { $height++; $add2 = $trans_row['add2']; }
        if ($trans_row['add3'] > 0) { $height++; $add3 = $trans_row['add3']; }
        if ($trans_row['ded1'] > 0) { $height++; $ded1 = $trans_row['ded1']; }
        if ($trans_row['ded2'] > 0) { $height++; $ded2 = $trans_row['ded2']; }
        if ($trans_row['ded3'] > 0) { $height++; $ded3 = $trans_row['ded3']; }
        if ($trans_row['ded4'] > 0) { $height++; $ded4 = $trans_row['ded4']; }
        if ($trans_row['ded5'] > 0) { $height++; $ded5 = $trans_row['ded5']; }
        if ($trans_row['ded6'] > 0) { $height++; $ded6 = $trans_row['ded6']; }
        if ($trans_row['ded7'] > 0) { $height++; $ded7 = $trans_row['ded7']; }
        if ($trans_row['ded8'] > 0) { $height++; $ded8 = $trans_row['ded8']; }
        if ($trans_row['hra_deduction'] > 0)    { $height++; $hra     = $trans_row['hra_deduction']; }
        if ($trans_row['advance_deduction'] > 0 || $trans_row['ticket_deduction'] > 0) {
            $height++;
            $ticket  = $trans_row['ticket_deduction'];
            $advance = $trans_row['advance_deduction'];
        }

        $y   = 80;
        $add = 0;
        $ded = 0;
        $rect_y = 0;

        // -- Additions detail --
        if ($total_addition > 0) {

            $pdf->SetXY(20, $y);
            $pdf->SetFont('Times','B',11);
            $pdf->Cell(30, 7, 'Additions', 0, 0, 'L');
            $pdf->SetFont('Times','',10);

            if ($add1 > 0) {
                $rect_y = $rect_y + $h + 1;
                $y = $y + 6;
                $pdf->SetXY(20, $y);
                $pdf->Cell(40, 7, $trans_row['add1_remarks'], 0, 0, 'L');
                $pdf->SetXY(168, $y);
                $pdf->Cell(15, 7, '+ '.$add1, 0, 0, 'R');
                $add += $trans_row['add1'];
            }
            if ($add2 > 0) {
                $rect_y = $rect_y + $h + 1;
                $y = $y + 6;
                $pdf->SetXY(20, $y);
                $pdf->Cell(40, 7, $trans_row['add2_remarks'], 0, 0, 'L');
                $pdf->SetXY(168, $y);
                $pdf->Cell(15, 7, '+ '.$add2, 0, 0, 'R');
                $add += $trans_row['add2'];
            }
            if ($trans_row['add3'] > 0) {
                $rect_y = $rect_y + $h + 1;
                $y = $y + 6;
                $pdf->SetXY(20, $y);
                $pdf->Cell(40, 7, $trans_row['add3_remarks'], 0, 0, 'L');
                $pdf->SetXY(168, $y);
                $pdf->Cell(15, 7, '+ '.$trans_row['add3'], 0, 0, 'R');
                $add += $trans_row['add3'];
            }

            $rect_y = $rect_y + $h + 1;
            $y = $y + 6;
            $pdf->SetFont('Times','B',11);
            $pdf->SetXY(40, $y);
            $pdf->Cell(10, 7, "Total", 0, 0, 'L');
            $pdf->SetXY(168, $y);
            $pdf->Cell(15, 7, '+ '.$total_addition, 0, 0, 'R');
            $y = $y + 6;
        }

        // -- Deductions detail --
        if ($total_deduction > 0) {

            $pdf->SetXY(20, $y);
            $pdf->SetFont('Times','B',11);
            $pdf->Cell(30, 7, 'Deductions', 0, 0, 'L');
            $pdf->SetFont('Times','',10);

            if ($ded1 > 0) {
                $rect_y = $rect_y + $h + 1;
                $y = $y + 6;
                $pdf->SetXY(20, $y);
                $pdf->Cell(40, 7, $trans_row['ded1_remarks'], 0, 0, 'L');
                $pdf->SetXY(168, $y);
                $pdf->Cell(15, 7, '- '.$ded1, 0, 0, 'R');
                $ded += $trans_row['ded1'];
            }
            if ($ded2 > 0) {
                $rect_y = $rect_y + $h + 1;
                $y = $y + 6;
                $pdf->SetXY(20, $y);
                $pdf->Cell(40, 7, $trans_row['ded2_remarks'], 0, 0, 'L');
                $pdf->SetXY(168, $y);
                $pdf->Cell(15, 7, '- '.$ded2, 0, 0, 'R');
                $ded += $trans_row['ded2'];
            }
            if ($ded3 > 0) {
                $rect_y = $rect_y + $h + 1;
                $y = $y + 6;
                $pdf->SetXY(20, $y);
                $pdf->Cell(40, 7, $trans_row['ded3_remarks'], 0, 0, 'L');
                $pdf->SetXY(168, $y);
                $pdf->Cell(15, 7, '- '.$ded3, 0, 0, 'R');
                $ded += $trans_row['ded3'];
            }
            if ($ded4 > 0) {
                $rect_y = $rect_y + $h + 1;
                $y = $y + 6;
                $pdf->SetXY(20, $y);
                $pdf->Cell(40, 7, $trans_row['ded4_remarks'], 0, 0, 'L');
                $pdf->SetXY(168, $y);
                $pdf->Cell(15, 7, '- '.$ded4, 0, 0, 'R');
                $ded += $trans_row['ded4'];
            }
            if ($ded5 > 0) {
                $rect_y = $rect_y + $h + 1;
                $y = $y + 6;
                $pdf->SetXY(20, $y);
                $pdf->Cell(40, 7, $trans_row['ded5_remarks'], 0, 0, 'L');
                $pdf->SetXY(168, $y);
                $pdf->Cell(15, 7, '- '.$ded5, 0, 0, 'R');
                $ded += $trans_row['ded5'];
            }
            if ($ded6 > 0) {
                $rect_y = $rect_y + $h + 1;
                $y = $y + 6;
                $pdf->SetXY(20, $y);
                $pdf->Cell(40, 7, $trans_row['ded6_remarks'], 0, 0, 'L');
                $pdf->SetXY(168, $y);
                $pdf->Cell(15, 7, '- '.$ded6, 0, 0, 'R');
                $ded += $trans_row['ded6'];
            }
            if ($ded7 > 0) {
                $rect_y = $rect_y + $h + 1;
                $y = $y + 6;
                $pdf->SetXY(20, $y);
                $pdf->Cell(40, 7, $trans_row['ded7_remarks'], 0, 0, 'L');
                $pdf->SetXY(168, $y);
                $pdf->Cell(15, 7, '- '.$ded7, 0, 0, 'R');
                $ded += $trans_row['ded7'];
            }
            if ($ded8 > 0) {
                $rect_y = $rect_y + $h + 1;
                $y = $y + 6;
                $pdf->SetXY(20, $y);
                $pdf->Cell(40, 7, $trans_row['ded8_remarks'], 0, 0, 'L');
                $pdf->SetXY(168, $y);
                $pdf->Cell(15, 7, '- '.$ded8, 0, 0, 'R');
                $ded += $trans_row['ded8'];
            }
            if ($advance > 0) {
                $rect_y = $rect_y + $h + 1;
                $y = $y + 6;
                $pdf->SetXY(20, $y);
                $pdf->Cell(40, 7, $trans_row['hra_deduction_remarks'].'/'.$trans_row['advance_ded_remarks'], 0, 0, 'L');
                $pdf->SetXY(168, $y);
                $hra_advance = $hra + $advance;
                $pdf->Cell(15, 7, '- '.$hra_advance, 0, 0, 'R');
                $ded += $hra_advance;
            }
            if ($hra > 0) {
                $rect_y = $rect_y + $h + 1;
                $y = $y + 6;
                $pdf->SetXY(20, $y);
                $pdf->Cell(40, 7, $trans_row['hra_deduction_remarks'], 0, 0, 'L');
                $pdf->SetXY(168, $y);
                $pdf->Cell(15, 7, '- '.$hra, 0, 0, 'R');
                $ded += $trans_row['hra_deduction'];
            }

            $y = $y + 6;
            $pdf->SetFont('Times','B',11);
            $pdf->SetXY(40, $y);
            $pdf->Cell(10, 7, "Total", 0, 0, 'L');
            $pdf->SetXY(168, $y);
            $pdf->Cell(15, 7, '- '.$total_deduction, 0, 0, 'R');
        }
    }

// -------------------------------------------------------
// Output the password-protected PDF
// -------------------------------------------------------
$pdf->SetDisplayMode('real');

ob_end_clean(); // Discard any buffered output before sending PDF headers

if ($show_deduction == 1) {
    $pdf->Output('Salary_slip_with_deduction.pdf', 'D');
} else {
    $pdf->Output('Salary slip.pdf', 'D');
}

?>