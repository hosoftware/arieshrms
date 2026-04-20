<?php
include '../connect.inc.php';

$date = $_GET['date'] ?? '';
$is_complete = $_GET['is_complete'] ?? '';
$userId = 5781;

if (empty($date) || $is_complete === '') {
    echo 'Missing required parameters';
    exit;
}

$SQL = "UPDATE tbl_time SET is_complete = ? WHERE user_id = ? AND date_log = ?";

$stmt = $mysqli->prepare($SQL);

if (!$stmt) {
    echo 'Prepare failed: ' . $conn->error;
}

$stmt->bind_param("iis", $is_complete, $userId, $date);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo 'Updated successfully';
    } else {
        echo 'No matching record found or no changes made';
    }
} else {
    echo 'Execute failed: ' . $stmt->error;
}

$stmt->close();
$mysqli->close();
?>