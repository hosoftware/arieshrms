<?php

    require_once "jwt_auth.php";
    
    $stmt = $mysqli->prepare("SELECT name, value FROM `tbl_daytype` WHERE status=1");
    $stmt->execute();
    $result = $stmt->get_result();
    $dayTypes = [];
    while ($row = $result->fetch_assoc()) {
        $dayTypes[] = $row;
    }
    
    if ($dayTypes) {
        echo json_encode([
            "status" => true,
            "data" => $dayTypes
        ]);
    } else {
        echo json_encode([
            "status" => false,
            "message" => "No day type is found."
        ]);
    }
    
    $stmt->close();

?>