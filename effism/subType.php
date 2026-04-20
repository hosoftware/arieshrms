<?php

require_once "jwt_auth.php";

$sql = "SELECT id, job_type_name FROM tbl_job_type WHERE type_status='Active' ORDER BY job_type_name ASC";

$stmt = $mysqli->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

$subTypes = [];
while ($row = $result->fetch_assoc()) {
    $subTypes[] = $row;
}

if ($subTypes) {
    echo json_encode([
        "status" => true,
        "data" => $subTypes
    ]);
} else {
    echo json_encode([
        "status" => false,
        "message" => "No main type is found."
    ]);
}

$stmt->close();


?>