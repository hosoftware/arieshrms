<?php

include("connect.inc.php");

require_once "jwt_auth.php";
$auth = requireAuth();

print_r($auth);

        
global $mysqli;
 
   

    
      $sql_type = "select *
          from tbl_lock_types order by sort_order";
$type_result = $mysqli->query($sql_type);
        
        if ($type_result->num_rows > 0) {

                  $typeArray['status'] = "Success";
                  $i=0;
            while ($type_row = $type_result->fetch_assoc()) {

                $id=$type_row['id'];
                
                        $typeArray['lockType'][$i]['id']=$id;
                        $typeArray['lockType'][$i]['type_name']=$type_row['type'];
                                                
                $i++;
                
              
            }
        } else {
            $typeArray['status'] = "Failed";
            $typeArray['error_message'] = "No Record Found";
            $typeArray['version'] = $version_row['version'];
        }
        echo json_encode($typeArray);
        exit;