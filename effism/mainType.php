<?php

require_once "jwt_auth.php";

$sql = "SELECT main_type_id, main_type_name FROM tbl_main_type";

$stmt = $mysqli->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

$mainTypes = [];
while ($row = $result->fetch_assoc()) {
    $mainTypes[] = $row;
}

if ($mainTypes) {
    echo json_encode([
        "status" => true,
        "data" => $mainTypes
    ]);
} else {
    echo json_encode([
        "status" => false,
        "message" => "No main type is found."
    ]);
}

$stmt->close();

?>