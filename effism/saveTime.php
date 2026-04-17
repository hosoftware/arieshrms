<?php

require_once "jwt_auth.php";
$auth = requireAuth();

$user_id       = $auth->uid;
$date_log      = $_POST['date'] ?? '';
$work_status   = $_POST['work_status'] ?? '';
$time_in       = $_POST['time_in'] ?? '';
$time_out      = $_POST['time_out'] ?? '';
$nwt           = $_POST['nwt'] ?? '';
$site_travel   = $_POST['site_travel'] ?? '';

if ($date_log == '') {
    echo json_encode([
        "status" => false,
        "message" => "Date is required."
    ]);
    exit;
}

// Check if record exists
$checkSql  = "SELECT id FROM tbl_time WHERE user_id = ? AND date_log = ?";
$checkStmt = $mysqli->prepare($checkSql);
$checkStmt->bind_param("is", $user_id, $date_log);
$checkStmt->execute();
$checkStmt->store_result();

$recordExists = $checkStmt->num_rows > 0;
$checkStmt->close();

if ($recordExists) {
    // Record exists — UPDATE
    $sql  = "UPDATE tbl_time SET time_in = ?, time_out = ?, nwt = ?, work_status = ?, site_travel=?
             WHERE user_id = ? AND date_log = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("sssssis", $time_in, $time_out, $nwt, $work_status, $site_travel, $user_id, $date_log);
} else {
    // Record does not exist — INSERT
    $sql  = "INSERT INTO tbl_time (user_id, date_log, time_in, time_out, nwt, work_status, site_travel)
             VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("issssss", $user_id, $date_log, $time_in, $time_out, $nwt, $work_status, $site_travel);
}

if ($stmt->execute()) {
    echo json_encode([
        "status" => true,
        "message" => $recordExists ? "Time record updated successfully." : "Time record saved successfully."
    ]);
} else {
    echo json_encode([
        "status" => false,
        "message" => "Failed to save time record: " . $stmt->error
    ]);
}
$stmt->close();
