<?php

require_once "jwt_auth.php";
$auth = requireAuth();

$userId = $auth->uid;

$date = trim($_POST['date'] ?? '');
if (empty($date)) {
    echo json_encode(["status" => false, "message" => "Date is required."]);
    exit;
}

$stmt = $mysqli->prepare("SELECT is_complete FROM tbl_time WHERE user_id = ? AND date_log = ? LIMIT 1");
$stmt->bind_param("is", $userId, $date);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        "status" => true,
        "is_complete" => (int)$row['is_complete']
    ]);
} else {
    echo json_encode([
        "status" => true,
        "is_complete" => 0,
        "message" => "No record found for the given date."
    ]);
}

$stmt->close();