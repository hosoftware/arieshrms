<?php

require_once "jwt_auth.php";
$auth = requireAuth();
$userId = $auth->uid;

//--- get job numbers for non-invoicable job types --//
$stmt = $mysqli->prepare("SELECT id, job_no FROM   tbl_job_numbers WHERE user_id=? AND status='Active'");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$jobNumbers = [];
while ($row = $result->fetch_assoc()) {
    $jobNumbers[] = $row;
}
$stmt->close();
echo json_encode([
    "status" => true,
    "message" => "Job numbers fetched successfully.",
    "data" => $jobNumbers,
]);
?>