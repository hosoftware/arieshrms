<?php

    require_once "jwt_auth.php";
    $auth = requireAuth();

    $query = "
    SELECT COALESCE(MAX(t.date_log), u.date_created) AS last_working_date
    FROM tbl_users u
    LEFT JOIN tbl_time t ON t.user_id = u.user_id AND t.is_complete = 1
    WHERE u.user_id = ?";

    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $auth->uid);
    $stmt->execute();

    $result = $stmt->get_result();
    $row    = $result->fetch_assoc();

    echo json_encode([
        "status" => true,
        "message" => "Last working date retrieved successfully.",
        "last_working_date" => $row['last_working_date']
    ]);

?>