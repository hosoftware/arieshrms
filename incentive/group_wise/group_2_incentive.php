<?php

function DateOfBirth($mysqli, $user_id)
{
    $stmt = $mysqli->prepare("SELECT YEAR(dob) AS dob_year FROM tbl_users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row['dob_year'] ?? null;
}

// FIX: guard against missing/invalid month/year before they're used to build $date.
// This is what was causing add_months() to receive a malformed string and throw
// the "Undefined array key 0" warning, which leaked into the output buffer and
// broke FPDF's Output() call later.
if (!ctype_digit((string)$inc_month) || !ctype_digit((string)$inc_year)) {
    echo "Error: Invalid month/year";
    exit;
}

$currency_result = $mysqli->query("select * from 0_currencies");
$currency_array = array();

$date = "01/" . $inc_month . "/" . $inc_year;

while ($currency_row = $currency_result->fetch_assoc()) {
    $curr_code = $currency_row['curr_abrev'];
    $rate = 1;
    $currency_array[$curr_code] = $rate;
}

$sql = "SELECT  e.sub_division_id,e.employee_code,e.id as employee_id,e.Name ,e.Bank_Name,e.IBAN,e.Designation,
e.division_id,e.company_id,i.*,d.company_currency , i.inc_month, i.inc_year,d2.name AS division,
d3.name AS sub_division, YEAR(u.dob) AS dob_year
FROM `0_emp_incentive` i 
left join  `0_emp` e  on e.id=i.employee_id left join  `0_dimensions` d  on d.id=e.company_id  
LEFT JOIN `0_dimensions` d2 ON d2.id = e.division_id
LEFT JOIN `0_dimensions` d3 ON d3.id = e.sub_division_id
LEFT JOIN `tbl_users` u ON u.hr_id = i.employee_id
where i.inc_month= '$inc_month' AND i.inc_year= '$inc_year' AND u.user_id='$user_id'";

$trans_result = $mysqli->query($sql);

$sqlBank = "SELECT *
FROM  0_bank ";

$rsltBank = $mysqli->query($sqlBank);
$arrBank = array();
while ($row_bank = $rsltBank->fetch_assoc()) {
    $arrBank[$row_bank['id']] = $row_bank['bank_name'];
}

$pdf = new PDF();

$user_password  = (string) DateOfBirth($mysqli, $user_id);
$owner_password = 'qwerty';

var_dump($user_id, $user_password, strlen($user_password));
exit; // temporary - remove after checking

$pdf->SetProtection(
    ['print'],
    $user_password,
    $owner_password
);

while ($trans_row = $trans_result->fetch_assoc()) {
    if (isset($_GET['loop']) && $_GET['loop'] == "success") {
        $inc_month   = $trans_row['inc_month'];
        $inc_year    = $trans_row['inc_year'];
        $month_name  = date('M', strtotime($inc_year . "-" . $inc_month . "-01"));
        $date_of_pay = date('d-m-Y', mktime(0, 0, 0, $inc_month + 1, '15', $inc_year));

        // FIX: rebuild $date from the row's own month/year before calling add_months(),
        // and guard it the same way, since this branch overwrites $inc_month/$inc_year
        // with values pulled from the DB row, which could just as easily be malformed.
        $date = "01/" . $inc_month . "/" . $inc_year;
        if (ctype_digit((string)$inc_month) && ctype_digit((string)$inc_year)) {
            $rate_date = add_months($date, 1);
        } else {
            $rate_date = null;
        }
    }

    $pdf->AddPage();
    $pdf->SetLineWidth(.5);

    $pdf->SetFont('Times', 'BU', 14);

    $pdf->Cell(190, 15, '', 0, 0, 'C');
    $pdf->Ln();

    $company_id = $trans_row['company_id'];
    $filePath = "images/company/" . $company_id . ".jpg";
    if (is_file($filePath)) {
        $filePath = "images/company/" . $company_id . ".jpg";
    } else {
        $company_id = '8';
        $filePath = "images/company/" . $company_id . ".jpg";
    }
    $pdf->image("images/company/" . $company_id . ".jpg", 7, 5, null, 33);

    $company_currency = $trans_row['company_currency'];

    if (($currency_array[$company_currency] == "") || ($currency_array[$company_currency] == 0)) {
        $rate = 1;
        $currency = 'AED';
    } else {
        $rate = $currency_array[$company_currency];
        $currency = $company_currency;
    }

    $pdf->Rect(18, 50, 175, 228);
    $pdf->SetXY(18, 50);
    $pdf->Cell(175, 10, 'Incentive and Reimbursement Sheet -   ' . $month_name . " " . $inc_year, 0, 0, 'C');

    $pdf->Line(18, 60, 193, 60);

    $pdf->SetFont('Times', 'B', 13);

    $pdf->SetXY(25, 62);
    $pdf->Cell(40, 7, 'Employee Name:', 0, 0, 'L');
    $pdf->Cell(120, 7, $trans_row['Name'], 0, 0, 'L');
    $pdf->SetXY(25, 69);
    $pdf->Cell(40, 7, 'Employee Code:', 0, 0, 'L');
    $pdf->Cell(120, 7, $trans_row['employee_code'], 0, 0, 'L');

    $pdf->SetXY(25, 76);
    $pdf->Cell(40, 7, 'Designation:', 0, 0, 'L');
    $pdf->Cell(120, 7, $trans_row['Designation'], 0, 0, 'L');
    $pdf->SetXY(25, 83);
    $pdf->Cell(40, 7, 'Department:', 0, 0, 'L');
    $pdf->Cell(120, 7, ($trans_row['division']) . "-" . ($trans_row['sub_division']), 0, 0, 'L');

    $pdf->Line(18, 90, 193, 90);

    $pdf->SetXY(18, 90);
    $pdf->Cell(115, 7, 'Details', 1, 0, 'C');
    $pdf->Cell(30, 7, 'Alloted', 1, 0, 'C');
    $pdf->Cell(30, 7, 'Eligible', 1, 0, 'C');
    $pdf->Line(18, 97, 193, 97);

    $pdf->Line(133, 90, 133, 164);
    $pdf->Line(163, 90, 163, 215);

    $pdf->SetFont('Times', '', 11);

    $alloted_total = 0;
    $eligible_total = 0;

    $pdf->SetXY(18, 100);
    if (trim($trans_row['gen_remarks']) != "")
        $pdf->Cell(115, 7, $trans_row['gen_remarks'], 0, 0, 'R');
    else
        $pdf->Cell(115, 7, "General Incentive", 0, 0, 'R');
    $pdf->Cell(30, 7, ($trans_row['inc_general'] / $rate), 0, 0, 'R');

    $alloted_total = $alloted_total + ($trans_row['inc_general'] / $rate);

    $eligible_inc_general = ($trans_row['inc_general'] * $trans_row['per_alloted'] / 100);
    $pdf->Cell(30, 7, ($eligible_inc_general / $rate), 0, 0, 'R');
    $eligible_total = $eligible_total + ($eligible_inc_general / $rate);

    $pdf->SetXY(18, 107);
    if ($trans_row['sp_remarks'] != "")
        $pdf->Cell(115, 7, $trans_row['sp_remarks'], 0, 0, 'R');
    else
        $pdf->Cell(115, 7, "Special Incentive", 0, 0, 'R');

    $pdf->Cell(30, 7, ($trans_row['inc_special'] / $rate), 0, 0, 'R');
    $alloted_total = $alloted_total + ($trans_row['inc_special'] / $rate);

    $pdf->Cell(30, 7, ($trans_row['inc_special'] / $rate), 0, 0, 'R');
    $eligible_total = $eligible_total + ($trans_row['inc_special'] / $rate);

    $pdf->SetXY(18, 114);
    if ($trans_row['project_remarks'] != "")
        $pdf->Cell(115, 7, $trans_row['project_remarks'], 0, 0, 'R');
    else
        $pdf->Cell(115, 7, "Project Allowance", 0, 0, 'R');

    $pdf->Cell(30, 7, ($trans_row['inc_project'] / $rate), 0, 0, 'R');
    $alloted_total = $alloted_total + ($trans_row['inc_project'] / $rate);

    $eligible_inc_project = ($trans_row['inc_project'] * $trans_row['per_alloted'] / 100);
    $pdf->Cell(30, 7, ($eligible_inc_project / $rate), 0, 0, 'R');
    $eligible_total = $eligible_total + ($eligible_inc_project / $rate);

    $pdf->SetXY(18, 121);

    if ($trans_row['job_full_remarks'] != "")
        $pdf->Cell(115, 7, $trans_row['job_full_remarks'], 0, 0, 'R');
    else
        $pdf->Cell(115, 7, "Job Exps", 0, 0, 'R');

    $pdf->Cell(30, 7, ($trans_row['job_full'] / $rate), 0, 0, 'R');
    $alloted_total = $alloted_total + ($trans_row['job_full'] / $rate);

    $pdf->Cell(30, 7, ($trans_row['job_full'] / $rate), 0, 0, 'R');
    $eligible_total = $eligible_total + ($trans_row['job_full'] / $rate);

    $pdf->SetXY(18, 128);
    if ($trans_row['div_deduction_remarks'] != "")
        $pdf->Cell(115, 7, $trans_row['div_deduction_remarks'], 0, 0, 'R');
    else
        $pdf->Cell(115, 7, 'Division Deduction', 0, 0, 'R');

    if ($trans_row['div_deduction'] > 0) {
        $pdf->Cell(30, 7, '0.00', 0, 0, 'R');
    } else
        $pdf->Cell(30, 7, '0.00', 0, 0, 'R');

    if ($trans_row['div_deduction'] > 0) {
        $pdf->Cell(30, 7, (-$trans_row['div_deduction'] / $rate), 0, 0, 'R');
        $eligible_total = $eligible_total - ($trans_row['div_deduction'] / $rate);
    } else
        $pdf->Cell(30, 7, '0.00', 0, 0, 'R');

    $pdf->SetXY(18, 135);
    if ($trans_row['div_hold_remarks'] != "")
        $pdf->Cell(115, 7, $trans_row['div_hold_remarks'], 0, 0, 'R');
    else
        $pdf->Cell(115, 7, 'Division Hold', 0, 0, 'R');

    if ($trans_row['div_hold'] > 0) {
        $pdf->Cell(30, 7, '0.00', 0, 0, 'R');
    } else
        $pdf->Cell(30, 7, '0.00', 0, 0, 'R');

    if ($trans_row['div_hold'] > 0) {
        $pdf->Cell(30, 7, (-$trans_row['div_hold'] / $rate), 0, 0, 'R');
        $eligible_total = $eligible_total - ($trans_row['div_hold'] / $rate);
    } else
        $pdf->Cell(30, 7, '0.00', 0, 0, 'R');

    $pdf->SetXY(18, 142);
    $pdf->Cell(115, 7, 'Division Release', 0, 0, 'R');

    if ($trans_row['div_release'] > 0) {
        $pdf->Cell(30, 7, $trans_row['div_release'], 0, 0, 'R');
        $alloted_total = $alloted_total + ($trans_row['div_release'] / $rate);
    } else
        $pdf->Cell(30, 7, '0.00', 0, 0, 'R');

    if ($trans_row['div_release'] > 0) {
        $pdf->Cell(30, 7, ($trans_row['div_release'] / $rate), 0, 0, 'R');
        $eligible_total = $eligible_total + ($trans_row['div_release'] / $rate);
    } else
        $pdf->Cell(30, 7, '0.00', 0, 0, 'R');

    $pdf->SetXY(18, 149);
    if ($trans_row['other_divisions_remarks'] != "")
        $pdf->Cell(115, 7, $trans_row['other_divisions_remarks'], 0, 0, 'R');
    else
        $pdf->Cell(115, 7, 'Other Division', 0, 0, 'R');

    $pdf->Cell(30, 7, ($trans_row['other_divisions'] / $rate), 0, 0, 'R');
    $alloted_total = $alloted_total + ($trans_row['other_divisions'] / $rate);
    $pdf->Cell(30, 7, ($trans_row['other_divisions'] / $rate), 0, 0, 'R');
    $eligible_total = $eligible_total + ($trans_row['other_divisions'] / $rate);

    $pdf->Line(18, 156, 193, 156);

    $pdf->SetXY(18, 156);
    $pdf->SetFont('Times', 'B', 12);
    $pdf->Cell(115, 8, "Total(" . $currency . ")", 0, 0, 'R');
    $pdf->Cell(30, 8, ($alloted_total), 0, 0, 'R');
    $pdf->Cell(30, 8, ($eligible_total), 0, 0, 'R');

    $pdf->Line(18, 164, 193, 164);

    $pdf->SetFont('Times', '', 11);

    $pdf->SetXY(18, 164);

    $pdf->SetFont('Times', 'BU', 11);
    $pdf->Cell(145, 6, "Additions/Deductions", 0, 0, 'C');

    $pdf->SetFont('Times', '', 11);

    $i = 170;

    if ($trans_row['royalty'] > 0) {
        $pdf->SetXY(18, $i);
        $pdf->Cell(145, 7, $trans_row['royalty_remarks'], 0, 0, 'R');
        $pdf->Cell(30, 7, ($trans_row['royalty'] / $rate), 0, 0, 'R');
        $eligible_total = $eligible_total + ($trans_row['royalty'] / $rate);
        $i = $i + 7;
    }

    if ($trans_row['petty_cash'] > 0) {
        $pdf->SetXY(18, $i);
        $pdf->Cell(145, 7, "Reimbursement", 0, 0, 'R');
        $pdf->Cell(30, 7, ($trans_row['petty_cash'] / $rate), 0, 0, 'R');
        $eligible_total = $eligible_total + ($trans_row['petty_cash'] / $rate);
        $i = $i + 7;
    }

    if (($trans_row['deduction1'] > 0) || ($trans_row['deduction1'] < 0)) {
        $pdf->SetXY(18, $i);
        $pdf->Cell(145, 7, $trans_row['deduction1_remarks'], 0, 0, 'R');
        $pdf->Cell(30, 7, ($trans_row['deduction1'] / $rate), 0, 0, 'R');
        $eligible_total = $eligible_total + ($trans_row['deduction1'] / $rate);
        $i = $i + 7;
    }

    if (($trans_row['deduction2'] > 0) || ($trans_row['deduction2'] < 0)) {
        $pdf->SetXY(18, $i);
        $pdf->Cell(145, 7, $trans_row['deduction2_remarks'], 0, 0, 'R');
        $pdf->Cell(30, 7, ($trans_row['deduction2'] / $rate), 0, 0, 'R');
        $eligible_total = $eligible_total + ($trans_row['deduction2'] / $rate);
        $i = $i + 7;
    }

    if (($trans_row['bmi_hold'] > 0) || ($trans_row['bmi_hold'] < 0)) {
        $pdf->SetXY(18, $i);
        $pdf->Cell(145, 7, "BMI" . $trans_row['bmi_remarks'], 0, 0, 'R');
        $pdf->Cell(30, 7, ($trans_row['bmi_hold'] / $rate), 0, 0, 'R');
        $eligible_total = $eligible_total + ($trans_row['bmi_hold'] / $rate);
        $i = $i + 7;
    }

    $pdf->Line(18, 205, 193, 205);

    $pdf->Line(18, 215, 193, 215);

    $pdf->SetLineWidth(.3);

    $pdf->SetXY(18, 205);
    $pdf->SetFont('Times', 'B', 13);
    $pdf->Cell(145, 10, "Net Total(" . $currency . ")", 1, 0, 'R');
    $pdf->Cell(30, 10, ($eligible_total), 0, 0, 'R');

    $pdf->SetLineWidth(.4);
    $pdf->SetFont('Times', 'B', 13);

    $pdf->SetFont('Times', '', 11);

    $y = 200;

    $y = $y + 30;
    $pdf->SetFont('Times', 'I', 10);
    $pdf->SetXY(25, $y);
    $pdf->Cell(145, 5, 'Eligiblity based on company terms and conditions', 1, 0, 'L');
    $y = $y + 5;
    $pdf->SetXY(25, $y);
    $pdf->Cell(145, 5, "Date of Pay $date_of_pay", 1, 0, 'L');
    $y = $y + 5;
    $pdf->SetXY(25, $y);

    if ($trans_row['is_cash'] == 1) {
        $pdf->Cell(100, 6, 'CASH PAID', 1, 0, 'L');
        $pdf->Cell(45, 6, '', 1, 0, 'L');
    } else {
        $pdf->Cell(100, 6, 'Transfered to: ' . $arrBank[$trans_row['Bank_Name']], 1, 0, 'L');
        $pdf->Cell(45, 6, $trans_row['IBAN'], 1, 0, 'L');
    }
}

$pdf->SetDisplayMode('real');

// FIX: discard any buffered warnings/notices right before sending the PDF binary,
// so a stray warning anywhere above can never again trigger
// "Some data has already been output, can't send PDF file".
if (ob_get_length()) {
    ob_clean();
}

$pdf->Output("Incentive Slip.pdf", 'D');