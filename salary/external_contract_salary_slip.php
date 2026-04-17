<?php
ob_start();

require_once('../connect.inc.php');
require_once('vendor/autoload.php');
require_once('fpdf_protection.php');

ini_set('display_errors', '');



    $month =03;
    $year = 2026;
    $emp_id=9149;


if (1) {

    $sql = " SELECT  (s.EBS+s.EFA+s.EHRA+s.EFOT+s.EMA+s.ECA+s.EPA+s.EFI+s.ECE+s.ETA+s.ETRA+s.EOA+s.EPAR+s.ESA+s.ESA3) as net_salary_no_decuction,
d2.name AS division,
d3.name AS sub_division,
e.id as employee_id,e.Name, e.Designation, e.Email,e.employee_code,e.sub_division_id,e.nationality, s.*
		FROM 0_salary s left join 0_emp e on e.id=s.emp_id
LEFT JOIN `0_dimensions` d2 ON d2.id = e.division_id
LEFT JOIN `0_dimensions` d3 ON d3.id = e.sub_division_id

		where   e.Email!='' and e.Email!='NA' and  s.month= '$month'  
			AND s.year= '$year'
			AND  is_mail = 0 and e.working=1 and emp_id=$emp_id ";
    
  $sql.="  order by e.Name LIMIT 20";
   

    $trans_result = $mysqli->query($sql);
   
    $date = $year . "-" . $month . "-16";

    echo "<table border = '1'>";
    
    $trans_row = $trans_result->fetch_assoc();
      
         
      
        $pdf = new FPDF();


        $net_sal = $trans_row['EBS'] + $trans_row['EFA'] + $trans_row['EHRA'] + $trans_row['EFOT'] + $trans_row['EMA'] +
                $trans_row['ECA'] + $trans_row['EPA'] + $trans_row['EFI'] + $trans_row['ECE'] + $trans_row['ETA'] + $trans_row['ETRA'] + $trans_row['EOA']+$trans_row['EDA']+$trans_row['ESA4']+$trans_row['EPAR']+ $trans_row['ESA']+ $trans_row['ESA3'];


$pdf->AddPage();
    $pdf->SetLineWidth(.5);

    $pdf->SetFont('Times','BU',14);

    //$pdf->image($dimension_id.".jpg",5,5,null,33);
    $pdf->Cell(190,15,'',0,0,'C');
    $pdf->Ln();

    //Centered text in a framed 20*10 mm cell and line break
    //$company_id = $trans_row['company_id'];




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

    $pdf->SetFont('Times','B',11);
    $pdf->SetLineWidth(.4);
    //$pdf->Rect(25,100,160,7);
    $pdf->SetXY(20,82);
    $pdf->Cell(40,7,'Fixed Package:',0,0,'L');
    //$pdf->Cell(65,7,'Eligible',1,0,'C');

    $pdf->SetFont('Times','',11);

    $pdf->SetXY(20,89);
    $pdf->Cell(40,6,'Basic Salary',0,0,'L');
    $pdf->Cell(10,6,$trans_row['currency'],0,0,'C');
    $pdf->Cell(25,6,$trans_row['basic_salary'],0,0,'R');

    $pdf->SetXY(20,95);
    $pdf->Cell(40,6,"Food Allowance",0,0,'L');
    $pdf->Cell(10,6,$trans_row['currency'],0,0,'C');
    $pdf->Cell(25,6,$trans_row['food_allowance'],0,0,'R');


    $pdf->SetXY(20,101);
    $pdf->Cell(40,6,"House Allowance",0,0,'L');
    $pdf->Cell(10,6,$trans_row['currency'],0,0,'C');
    $pdf->Cell(25,6,$trans_row['house_allowance'],0,0,'R');


    $pdf->SetXY(20,107);
    $pdf->Cell(40,6,"Fixed Overtime",0,0,'L');
    $pdf->Cell(10,6,$trans_row['currency'],0,0,'C');
    $pdf->Cell(25,6,$trans_row['fixed_overtime'],0,0,'R');

    $pdf->SetXY(20,113);
    $pdf->Cell(40,6,"Mobile Allowance",0,0,'L');
    $pdf->Cell(10,6,$trans_row['currency'],0,0,'C');
    $pdf->Cell(25,6,$trans_row['mobile_allowance'],0,0,'R');

    $pdf->SetXY(20,119);
    $pdf->Cell(40,6,"Car Allowance",0,0,'L');
    $pdf->Cell(10,6,$trans_row['currency'],0,0,'C');
    $pdf->Cell(25,6,$trans_row['car_allowance'],0,0,'R');

    $pdf->SetXY(20,125);
    $pdf->Cell(40,6,"Petrol Allowance",0,0,'L');
    $pdf->Cell(10,6,$trans_row['currency'],0,0,'C');
    $pdf->Cell(25,6,$trans_row['petrol_allowance'],0,0,'R');

    $pdf->SetXY(20,131);
    $pdf->Cell(40,6,"Fixed Incentive",0,0,'L');
    $pdf->Cell(10,6,$trans_row['currency'],0,0,'C');
    $pdf->Cell(25,6,$trans_row['fixed_incentive'],0,0,'R');

    $pdf->SetXY(20,137);
    $pdf->Cell(40,6,"Child Education",0,0,'L');
    $pdf->Cell(10,6,$trans_row['currency'],0,0,'C');
    $pdf->Cell(25,6,$trans_row['child_education_allowance'],0,0,'R');

    $pdf->SetXY(20,143);
    $pdf->Cell(40,6,"Traveling Allowance",0,0,'L');
    $pdf->Cell(10,6,$trans_row['currency'],0,0,'C');
    $pdf->Cell(25,6,$trans_row['traveling_allowance'],0,0,'R');

    $pdf->SetXY(20,149);
    $pdf->Cell(40,6,"Target Allowance",0,0,'L');
    $pdf->Cell(10,6,$trans_row['currency'],0,0,'C');
    $pdf->Cell(25,6,$trans_row['target_allowance'],0,0,'R');

    $pdf->SetXY(20,155);
    $pdf->Cell(40,6,"Uniform Allowance",0,0,'L');
    $pdf->Cell(10,6,$trans_row['currency'],0,0,'C');
    $pdf->Cell(25,6,$trans_row['other_allowance'],0,0,'R');
    

    $pdf->SetXY(20,161);
    $pdf->Cell(40,6,"Dearness Allowance",0,0,'L');
    $pdf->Cell(10,6,$trans_row['currency'],0,0,'C');
    $pdf->Cell(25,6,$trans_row['dearness_allowance'],0,0,'R');
    

    $pdf->SetXY(20,167);
    $pdf->Cell(40,6,"Baby Allowance",0,0,'L');
    $pdf->Cell(10,6,$trans_row['currency'],0,0,'C');
    $pdf->Cell(25,6,$trans_row['special_allowance4'],0,0,'R');
    




    $pdf->SetXY(20,173);
    $pdf->Cell(40,6,"Special Allowance 1",0,0,'L');
    $pdf->Cell(10,6,$trans_row['currency'],0,0,'C');
    $pdf->Cell(25,6,$trans_row['parent_allowance'],0,0,'R');
    
    $pdf->SetXY(20,179);
    $pdf->Cell(40,6,"Special Allowance 2",0,0,'L');
    $pdf->Cell(10,6,$trans_row['currency'],0,0,'C');
    $pdf->Cell(25,6,$trans_row['special_allowance'],0,0,'R');
    
    
    $pdf->SetXY(20,185);
    $pdf->Cell(40,6,"Special Allowance 3",0,0,'L');
    $pdf->Cell(10,6,$trans_row['currency'],0,0,'C');
    $pdf->Cell(25,6,$trans_row['special_allowance3'],0,0,'R');
    



    $pdf->SetLineWidth(.3);
    //$pdf->Rect(30,201,80,10);

    $pdf->SetXY(20,195);
    $pdf->SetFont('Times','B',13);
    $pdf->Cell(40,7,'Total',0,0,'L');
    $pdf->Cell(10,7,'',0,0,'C');
    $pdf->Cell(25,7,$trans_row['gross_salary'],0,0,'R');

    $pdf->SetLineWidth(.4);
    $pdf->SetFont('Times','',11);
    //$pdf->Rect(120,100,65,115);
    //ACTUAL SALARY

    $pdf->SetXY(120,82);
    $pdf->Cell(50,7,'Number of Working Days',0,0,'L');
    //$pdf->Cell(10,7,'',0,0,'R');
    $pdf->Cell(10,7,$trans_row['NOWD'],0,0,'R');


    $pdf->SetXY(120,89);
    $pdf->Cell(35,6,'Basic Salary',0,0,'L');
    $pdf->Cell(15,6,$trans_row['currency'],0,0,'C');
    $pdf->Cell(15,6,$trans_row['EBS'],0,0,'R');

    $pdf->SetXY(120,95);
    $pdf->Cell(35,6,"Food Allowance",0,0,'L');
    $pdf->Cell(15,6,$trans_row['currency'],0,0,'C');
    $pdf->Cell(15,6,$trans_row['EFA'],0,0,'R');


    $pdf->SetXY(120,101);
    $pdf->Cell(35,6,"House Allowance",0,0,'L');
    $pdf->Cell(15,6,$trans_row['currency'],0,0,'C');
    $pdf->Cell(15,6,$trans_row['EHRA'],0,0,'R');


    $pdf->SetXY(120,107);
    $pdf->Cell(35,6,"Fixed Overtime",0,0,'L');
    $pdf->Cell(15,6,$trans_row['currency'],0,0,'C');
    $pdf->Cell(15,6,$trans_row['EFOT'],0,0,'R');

    $pdf->SetXY(120,113);
    $pdf->Cell(35,6,"Mobile Allowance",0,0,'L');
    $pdf->Cell(15,6,$trans_row['currency'],0,0,'C');
    $pdf->Cell(15,6,$trans_row['EMA'],0,0,'R');

    $pdf->SetXY(120,119);
    $pdf->Cell(35,6,"Car Allowance",0,0,'L');
    $pdf->Cell(15,6,$trans_row['currency'],0,0,'C');
    $pdf->Cell(15,6,$trans_row['ECA'],0,0,'R');

    $pdf->SetXY(120,125);
    $pdf->Cell(35,6,"Petrol Allowance",0,0,'L');
    $pdf->Cell(15,6,$trans_row['currency'],0,0,'C');
    $pdf->Cell(15,6,$trans_row['EPA'],0,0,'R');

    $pdf->SetXY(120,131);
    $pdf->Cell(35,6,"Fixed Incentive",0,0,'L');
    $pdf->Cell(15,6,$trans_row['currency'],0,0,'C');
    $pdf->Cell(15,6,$trans_row['EFI'],0,0,'R');

    $pdf->SetXY(120,137);
    $pdf->Cell(35,6,"Child Education",0,0,'L');
    $pdf->Cell(15,6,$trans_row['currency'],0,0,'C');
    $pdf->Cell(15,6,$trans_row['ECE'],0,0,'R');

    $pdf->SetXY(120,143);
    $pdf->Cell(35,6,"Traveling Allowance",0,0,'L');
    $pdf->Cell(15,6,$trans_row['currency'],0,0,'C');
    $pdf->Cell(15,6,$trans_row['ETA'],0,0,'R');

    $pdf->SetXY(120,149);
    $pdf->Cell(35,6,"Target Allowance",0,0,'L');
    $pdf->Cell(15,6,$trans_row['currency'],0,0,'C');
    $pdf->Cell(15,6,$trans_row['ETRA'],0,0,'R');

    $pdf->SetXY(120,155);
    $pdf->Cell(35,6,"Uniform Allowance",0,0,'L');
    $pdf->Cell(15,6,$trans_row['currency'],0,0,'C');
    $pdf->Cell(15,6,$trans_row['EOA'],0,0,'R');

    $pdf->SetXY(120,161);
    $pdf->Cell(35,6,"Dearness Allowance",0,0,'L');
    $pdf->Cell(15,6,$trans_row['currency'],0,0,'C');
    $pdf->Cell(15,6,$trans_row['EDA'],0,0,'R');

    $pdf->SetXY(120,167);
    $pdf->Cell(35,6,"Baby Allowance",0,0,'L');
    $pdf->Cell(15,6,$trans_row['currency'],0,0,'C');
    $pdf->Cell(15,6,$trans_row['ESA4'],0,0,'R');
    
    $pdf->SetXY(120,173);
    $pdf->Cell(35,6,"Special Allowance 1",0,0,'L');
    $pdf->Cell(15,6,$trans_row['currency'],0,0,'C');
    $pdf->Cell(15,6,$trans_row['EPAR'],0,0,'R');
    
    $pdf->SetXY(120,179);
    $pdf->Cell(35,6,"Special Allowance 2",0,0,'L');
    $pdf->Cell(15,6,$trans_row['currency'],0,0,'C');
    $pdf->Cell(15,6,$trans_row['ESA'],0,0,'R');
    
    $pdf->SetXY(120,185);
    $pdf->Cell(35,6,"Special Allowance 3",0,0,'L');
    $pdf->Cell(15,6,$trans_row['currency'],0,0,'C');
    $pdf->Cell(15,6,$trans_row['ESA3'],0,0,'R');
    
    


    $pdf->SetFont('Times','',11);
    $pdf->SetXY(120,117);

    $pdf->SetLineWidth(.3);


    $pdf->SetXY(120,195);
    $pdf->SetFont('Times','B',13);
    $pdf->Cell(35,7,'Total',0,0,'L');
    $pdf->Cell(15,7,'',0,0,'R');
    $pdf->Cell(15,7,$net_sal,0,0,'R');


        $pdf->SetXY(20,210);
        $pdf->SetFont('Times','B',11);
        $pdf->Cell(30,7,'Additions/Deductions',0,0,'L');
       
    $y = 210 + 2;
    $rect_y = 0;

    $pdf->SetFont('Times','',11);
    $height = 0;
    $add1 = 0;
    $add2 = 0;
    $ded1 = 0;
    $ded2 = 0;
    $ded3 = 0;
    $ded4 = 0;
    $ded5 = 0;
    $ded6 = 0;
    $ded7 = 0;
    $ded8 = 0;
    $hra = 0;
    $ticket = 0;
    $advance = 0;

    /**/
    $total_addition = $trans_row['add1']+$trans_row['add2']+$trans_row['add3'];
    $total_deduction = $trans_row['ded1']+$trans_row['ded2']+$trans_row['ded3']
    +$trans_row['ded4']+$trans_row['ded5']+$trans_row['ded6']
    +$trans_row['ded7']+$trans_row['ded8']+$trans_row['hra_deduction']
    +$trans_row['advance_deduction'];
    if($total_addition>0) {
    	$height++;
    }



    //exit;
    $add = 0;
    $ded = 0;
    $total = 0;
                    $rect_y = 25;
        
        $y = 217;
        
    
    	if ($total_addition > 0) {
    		$rect_y = $rect_y + $h + 1;
                   

    		$pdf->SetXY(20,$y);
    		$pdf->Cell(40,7,"Addition",0,0,'L');
    		$pdf->SetXY(168,$y);
    		$pdf->Cell(15,7,'+ '.$total_addition,0,0,'R');
    		//$add += $trans_row['add1'];
                $y = $y + 7;
    	}
    	if ($total_deduction > 0) {
    		$rect_y = $rect_y + $h + 1;

    		$pdf->SetXY(20,$y);
    		$pdf->Cell(40,7,"Deduction",0,0,'L');
    		$pdf->SetXY(168,$y);
    		$pdf->Cell(15,7,'- '.$total_deduction,0,0,'R');
    		//$ded += $trans_row['ded1'];
                 $y = $y+5;
    	}

  
    $pdf->SetFont('Times','B',11);


        $total = $total_addition;
        $total = $total - $total_deduction;


             $y = 230;

        $pdf->SetXY(145,$y);
        $pdf->Cell(10,7,"Total",0,0,'L');
        //$pdf->Cell(2,7,'',0,0,'C');
        $pdf->SetXY(168,$y);
        $pdf->Cell(15,7,$total,0,0,'R');
        $rect_y = $rect_y + $h;
    
        $pdf->Rect(20,210,170,28);

        $pdf->SetFont('Times','B',13);

      
                    
 

        $y = 240;

        $pdf->SetXY(75,$y);
        $pdf->Cell(30,7,"Net Salary",0,0,'L');
        $pdf->Cell(10,7,$trans_row['currency'],0,0,'C');
        $pdf->Cell(25,7,ceil($trans_row['net_salary']).'.00',0,0,'R');



    $pdf->SetFont('Times','I',10);
    //$pdf->SetXY(25,$y);
    //$pdf->Cell(30,7,'Prepared By',0,0,'L');
    //$pdf->Cell(30,7,$trans_row['prepared_by'],0,0,'R');

     $y = 250;
    
    $pdf->SetXY(25,250);
    $pdf->Cell(50,7,"Date of Pay ".$trans_row['DAP'],0,0,'L');

    /* $pdf->SetXY(25, 252);
      $pdf->Cell(50,7,"Transfered to: ".$trans_row['bank_name']." A/c: ".$trans_row['acc_no'],0,0,'L');
     */
    $y =  257;
    $pdf->SetXY(25,$y);
    
    $y = $y + $h;
    $pdf->SetXY(125,$y);
   // $pdf->Cell(0, 5, "Page " . $pdf->PageNo() . "/{nb}", 0, 1);
   
    if($total_addition>0 || $total_deduction>0) {
    	$pdf->AddPage();
    	$pdf->SetLineWidth(.5);
    	
    	$pdf->SetFont('Times','BU',14);
    	
    	//$pdf->image($dimension_id.".jpg",5,5,null,33);
    	$pdf->Cell(190,15,'',0,0,'C');
    	$pdf->Ln();
    	
    	//Centered text in a framed 20*10 mm cell and line break
    	//$company_id = $trans_row['company_id'];
    	
    	 
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
    $pdf->Cell(50,7,$trans_row['sub_division_id'],0,0,'L');

    	
    	if ($trans_row['revision'] != 0) {
    		$pdf->SetFont('Times','',11);
    		$pdf->SetXY(170,73);
    		$pdf->Cell(175,7,'Revision #'.$trans_row['revision'],0,0,'L');
    	}
    	$h=6;
    	if ($trans_row['add1'] > 0) {
    		$height++;
    		$add1 = $trans_row['add1'];
    	}
    	if ($trans_row['add2'] > 0) {
    		$height++;
    		$add2 = $trans_row['add2'];
    	}
    	if ($trans_row['add3'] > 0) {
    		$height++;
    		$add3 = $trans_row['add3'];
    	}
    	if ($trans_row['ded5'] > 0) {
    		$height++;
    		$ded5 = $trans_row['ded5'];
    	}
    	if ($trans_row['ded1'] > 0) {
    		$height++;
    		$ded1 = $trans_row['ded1'];
    	}
    	if ($trans_row['ded2'] > 0) {
    		$height++;
    		$ded2 = $trans_row['ded2'];
    	}
    	if ($trans_row['ded3'] > 0) {
    		$height++;
    		$ded3 = $trans_row['ded3'];
    	}
    	if ($trans_row['ded4'] > 0) {
    		$height++;
    		$ded4 = $trans_row['ded4'];
    	}
    	if ($trans_row['ded5'] > 0) {
    		$height++;
    		$ded5 = $trans_row['ded5'];
    	}
    	if ($trans_row['ded6'] > 0) {
    		$height++;
    		$ded6 = $trans_row['ded6'];
    	}
    	if ($trans_row['ded7'] > 0) {
    		$height++;
    		$ded7 = $trans_row['ded7'];
    	}
    	if ($trans_row['ded8'] > 0) {
    		$height++;
    		$ded8 = $trans_row['ded8'];
    	}
    	if ($trans_row['hra_deduction'] > 0) {
    		$height++;
    		$hra = $trans_row['hra_deduction'];
    	}
    	if ($trans_row['advance_deduction'] > 0 || $trans_row['ticket_deduction'] > 0) {
    		$height++;
    		$ticket = $trans_row['ticket_deduction'];
    		$advance = $trans_row['advance_deduction'];
    	}
    	$y=80;
    		if ($total_addition>0) {
    		
    			$pdf->SetXY(20,$y);
    			$pdf->SetFont('Times','B',11);
    			$pdf->Cell(30,7,'Additions',0,0,'L');
    			//$pdf->Cell(25,7,$trans_row['gross_salary'],0,0,'R');
    			//$pdf->Line(20, 173, 190, 173);
    			$pdf->SetFont('Times','',10);
    		if ($add1 > 0) {
    			$rect_y = $rect_y + $h + 1;
    			$y = $y + 6;
    			$pdf->SetXY(20,$y);
    			$pdf->Cell(40,7,$trans_row['add1_remarks'],0,0,'L');
    			$pdf->SetXY(168,$y);
    			$pdf->Cell(15,7,'+ '.$add1,0,0,'R');
    			$add += $trans_row['add1'];
    		}
    	
    		if ($add2 > 0) {
    			$rect_y = $rect_y + $h + 1;
    			$y = $y + 6;
    			$pdf->SetXY(20,$y);
    			$pdf->Cell(40,7,$trans_row['add2_remarks'],0,0,'L');
    			$pdf->SetXY(168,$y);
    			$pdf->Cell(15,7,'+ '.$add2,0,0,'R');
    			$add += $trans_row['add2'];
    		}
    		if ($trans_row['add3'] > 0) {
    			$rect_y = $rect_y + $h + 1;
    			$y = $y + 6;
    			$pdf->SetXY(20,$y);
    			$pdf->Cell(40,7,$trans_row['add3_remarks'],0,0,'L');
    			$pdf->SetXY(168,$y);
    			$pdf->Cell(15,7,'+ '.$trans_row['add3'],0,0,'R');
    			$add += $trans_row['add3'];
    		}
    		$rect_y = $rect_y + $h + 1;
    		$y = $y + 6;
    		$pdf->SetFont('Times','B',11);
    		$pdf->SetXY(40,$y);
    		$pdf->Cell(10,7,"Total",0,0,'L');
    		//$pdf->Cell(2,7,'',0,0,'C');
    		$pdf->SetXY(168,$y);
    		$pdf->Cell(15,7,'+ '.$total_addition,0,0,'R');
    		$y = $y + 6;
    		}
    		if ($total_deduction>0) {
    		
    			$pdf->SetXY(20,$y);
    			$pdf->SetFont('Times','B',11);
    			$pdf->Cell(30,7,'Deductions',0,0,'L');
    			//$pdf->Cell(25,7,$trans_row['gross_salary'],0,0,'R');
    			//$pdf->Line(20, 173, 190, 173);
    		
    		
    			$pdf->SetFont('Times','',10);
    	
    		if ($ded1 > 0) {
    			$rect_y = $rect_y + $h + 1;
    			$y = $y + 6;
    			$pdf->SetXY(20,$y);
    			$pdf->Cell(40,7,$trans_row['ded1_remarks'],0,0,'L');
    			$pdf->SetXY(168,$y);
    			$pdf->Cell(15,7,'- '.$ded1,0,0,'R');
    			$ded += $trans_row['ded1'];
    		}
    	
    		if ($ded2 > 0) {
    			$rect_y = $rect_y + $h + 1;
    			$y = $y + 6;
    			$pdf->SetXY(20,$y);
    			$pdf->Cell(40,7,$trans_row['ded2_remarks'],0,0,'L');
    			$pdf->SetXY(168,$y);
    			$pdf->Cell(15,7,'- '.$ded2,0,0,'R');
    			$ded += $trans_row['ded2'];
    		}
    	
    		if ($ded3 > 0) {
    			$rect_y = $rect_y + $h + 1;
    			$y = $y + 6;
    			$pdf->SetXY(20,$y);
    			$pdf->Cell(40,7,$trans_row['ded3_remarks'],0,0,'L');
    			$pdf->SetXY(168,$y);
    			$pdf->Cell(15,7,'- '.$ded3,0,0,'R');
    			$ded += $trans_row['ded3'];
    		}
    		if ($ded4 > 0) {
    			$rect_y = $rect_y + $h + 1;
    			$y = $y + 6;
    			$pdf->SetXY(20,$y);
    			$pdf->Cell(40,7,$trans_row['ded4_remarks'],0,0,'L');
    			$pdf->SetXY(168,$y);
    			$pdf->Cell(15,7,'- '.$ded4,0,0,'R');
    			$ded += $trans_row['ded4'];
    		}
    		if ($ded5 > 0) {
    			$rect_y = $rect_y + $h + 1;
    			$y = $y + 6;
    			$pdf->SetXY(20,$y);
    			$pdf->Cell(40,7,$trans_row['ded5_remarks'],0,0,'L');
    			$pdf->SetXY(168,$y);
    			$pdf->Cell(15,7,'- '.$ded5,0,0,'R');
    			$ded += $trans_row['ded5'];
    		}
    		if ($ded6 > 0) {
    			$rect_y = $rect_y + $h + 1;
    			$y = $y + 6;
    			$pdf->SetXY(20,$y);
    			$pdf->Cell(40,7,$trans_row['ded6_remarks'],0,0,'L');
    			$pdf->SetXY(168,$y);
    			$pdf->Cell(15,7,'- '.$ded6,0,0,'R');
    			$ded += $trans_row['ded6'];
    		}
    		if ($ded7 > 0) {
    			$rect_y = $rect_y + $h + 1;
    			$y = $y + 6;
    			$pdf->SetXY(20,$y);
    			$pdf->Cell(40,7,$trans_row['ded7_remarks'],0,0,'L');
    			$pdf->SetXY(168,$y);
    			$pdf->Cell(15,7,'- '.$ded7,0,0,'R');
    			$ded += $trans_row['ded7'];
    		}
    		if ($ded8 > 0) {
    			$rect_y = $rect_y + $h + 1;
    			$y = $y + 6;
    			$pdf->SetXY(20,$y);
    			$pdf->Cell(40,7,$trans_row['ded8_remarks'],0,0,'L');
    			$pdf->SetXY(168,$y);
    			$pdf->Cell(15,7,'- '.$ded8,0,0,'R');
    			$ded += $trans_row['ded8'];
    		}
    		if ($advance > 0) {
    			$rect_y = $rect_y + $h + 1;
    			$y = $y + 6;
    			$pdf->SetXY(20,$y);
    			$pdf->Cell(40,7,$trans_row['hra_deduction_remarks'].'/'.$trans_row['advance_ded_remarks'],0,0,'L');
    			$pdf->SetXY(168,$y);
    			$hra_advance = $hra + $advance;
    			$pdf->Cell(15,7,'- '.$hra_advance,0,0,'R');
    			$ded += $hra_advance;
    		} 
    		if ($hra > 0) {
    			$rect_y = $rect_y + $h + 1;
    			$y = $y + 6;
    			$pdf->SetXY(20,$y);
    			$pdf->Cell(40,7,$trans_row['hra_deduction_remarks'],0,0,'L');
    			$pdf->SetXY(168,$y);
    			$pdf->Cell(15,7,'- '.$hra,0,0,'R');
    			$ded += $trans_row['hra_deduction'];
    		}
    		$y = $y + 6;
    		$pdf->SetFont('Times','B',11);
    		$pdf->SetXY(40,$y);
    		$pdf->Cell(10,7,"Total",0,0,'L');
    		//$pdf->Cell(2,7,'',0,0,'C');
    		$pdf->SetXY(168,$y);
    		$pdf->Cell(15,7,'- '.$total_deduction,0,0,'R');
    		}
    		
    	
    	 
    }
  
    $y = $y + 6;
    $pdf->SetXY(125,$y);


 
    
    //}
        //display_error($trans_row['Name']);
       $pdf->SetDisplayMode('real');

ob_end_clean(); // Discard any buffered output before sending PDF headers

    $pdf->Output('Salary_slip_with_deduction.pdf', 'D');

        

    
    
    
}
/*
  $ids = trim($ids,',');
  meta_forward("salary_slip_send_to_email.php","flagMsg=Y&ids=".$ids);
 */
/*
  $pdf->SetDisplayMode('real');
  $pdf->Output("Salary slip.pdf",'D'); */
?>
