<?php

    require_once "jwt_auth.php";
 
    $work_status = trim($_POST['work_status'] ?? '');
 
    $stmt = $mysqli->prepare("SELECT leave_type_name, name FROM tbl_leavetype WHERE active=1 AND parent=? ORDER BY name ASC");
    $stmt->bind_param("s", $work_status);
    $stmt->execute();
    $result = $stmt->get_result();
    $leaveTypes = [];
    while ($row = $result->fetch_assoc()) {
        $leaveTypes[] = $row;
    }
    
    echo json_encode([
        "status" => true,
        "data" => $leaveTypes
    ]);
    
    $stmt->close();

?>