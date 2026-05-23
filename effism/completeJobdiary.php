<?php

require_once "jwt_auth.php";
$auth = requireAuth();

$userId = $auth->uid;
ini_set('date.timezone', getTimezone($auth));

$date_report = trim($_POST['date'] ?? '');
if (empty($date_report)) {
    echo json_encode(["status" => false, "message" => "Date is required."]);
    exit;
}

$datetime = date("Y-m-d H:i:s");


//--cf date validation--//
$stmtCfCheck = $mysqli->prepare("
    SELECT workreport_id, taskname, cf_date, main_type, job_no, description, status, job_type, est_time, target_date, row_id, delegation_id 
    FROM   tbl_workreports
    WHERE  user_id     = ?
      AND  date_report = ?
      AND  status      != 100 
      AND taskname!=''
");
$stmtCfCheck->bind_param("ss", $userId, $date_report);
$stmtCfCheck->execute();
$cfCheckResult = $stmtCfCheck->get_result();
$cfRows       = $cfCheckResult->fetch_all(MYSQLI_ASSOC);
$stmtCfCheck->close();

$cfErrors = []; 

foreach ($cfRows as $cfRow) {
    // 1. cf_date must not be empty
    if (empty(trim($cfRow['cf_date'] ?? '')) || $cfRow['cf_date'] == '0000-00-00') {
        $cfErrors[] = [
            'workreport_id' => $cfRow['workreport_id'],
            'taskname'      => $cfRow['taskname'],
            'cf_date'       => $cfRow['cf_date'],
            'reason'        => 'Carry forward date is missing.',
        ];
    // 2. cf_date must be after date_report
    } elseif (strtotime($cfRow['cf_date']) <= strtotime($date_report)) {
        $cfErrors[] = [
            'workreport_id' => $cfRow['workreport_id'],
            'taskname'      => $cfRow['taskname'],
            'cf_date'       => $cfRow['cf_date'],
            'reason'        => 'Carry forward date must be after the report date.',
        ];
    }
}


if (!empty($cfErrors)) {
    echo json_encode([
        "status"  => false,
        "message" => "Please select a proper carry forward date for all incomplete tasks.",
        "sub_error"  => !empty($cfErrors) ? true : false,
        "errors"  => $cfErrors,
    ]);
    exit();
}


//--job number validation--//
$stmtjobNoCheck = $mysqli->prepare("
    SELECT workreport_id, taskname, cf_date, main_type, job_no 
    FROM  tbl_workreports
    WHERE  user_id = ? AND  date_report = ? AND taskname!=''
");
$stmtjobNoCheck->bind_param("ss", $userId, $date_report);
$stmtjobNoCheck->execute();
$jobResult = $stmtjobNoCheck->get_result();
$jobNoRows = $jobResult->fetch_all(MYSQLI_ASSOC);
$stmtjobNoCheck->close();

$jobNoErrors = [];

foreach ($jobNoRows as $jobNoRow) {
    if ($jobNoRow['main_type'] == "1" && trim($jobNoRow['job_no'] ?? '') == "") {
        $jobNoErrors[] = [
            'workreport_id' => $jobNoRow['workreport_id'],
            'taskname'      => $jobNoRow['taskname'],
            'reason'        => 'No Job number.',
        ];
    }
}

if (!empty($jobNoErrors)) {
    echo json_encode([
        "status"  => false,
        "message" => "Please add Job number for Invoiceble job.",
        "sub_error"  => !empty($jobNoErrors) ? true : false,
        "errors"  => $jobNoErrors,
    ]);
    exit();
}


    //-- Delegation cf_date validation --//
    // For delegated tasks (delegation_id > 0), cf_date must not exceed tbl_delegation.target_date
    $delErrors=[];
    $stmtDelCheck = $mysqli->prepare("
    SELECT  wr.workreport_id, wr.taskname, wr.cf_date, d.target_date
    FROM    tbl_workreports  wr
    JOIN    tbl_delegation   d  ON d.delegation_id = wr.delegation_id
    WHERE   wr.user_id      = ?
    AND   wr.date_report  = ?
    AND   wr.status       != 100
    AND   wr.delegation_id > 0 
    AND d.delegate_type=1
    ");
    $stmtDelCheck->bind_param("ss", $userId, $date_report);
    $stmtDelCheck->execute();
    $delCheckResult = $stmtDelCheck->get_result();
    $delRows        = $delCheckResult->fetch_all(MYSQLI_ASSOC);
    $stmtDelCheck->close();

    foreach ($delRows as $delRow) {
        if (strtotime($delRow['cf_date']) > strtotime($delRow['target_date'])) {
            $delErrors[]=[
                'workreport_id' => $delRow['workreport_id'],
                'taskname'    => $delRow['taskname'],
                'cf_date'     => $delRow['cf_date'],
                'target_date' => $delRow['target_date'],
                'Message'    => 'Inavalid carry forward date.',
            ];
        }
    }

    if (!empty($delErrors)) {
        echo json_encode([
            "status"  => false,
            "message" => "Carry forward date cannot exceed the delegation target date.",
            "sub_error"  => !empty($delErrors) ? true : false,
            "errors"  => $delErrors,
        ]);
        exit();
    }


//1. Fetch time data from tbl_time
$stmtTblTime = $mysqli->prepare("
    SELECT time_in, time_out, nwt, extra_break, home, night,
          site_travel, leave_hours, work_status
    FROM   tbl_time
    WHERE  user_id  = ?
      AND  date_log = ?
    LIMIT  1
");
$stmtTblTime->bind_param("is", $userId, $date_report);
$stmtTblTime->execute();
$timeRow = $stmtTblTime->get_result()->fetch_assoc();
$stmtTblTime->close();

if (!$timeRow) {
    echo json_encode([
        "status" => false,
        "message" => "No time record found for the given date."
    ]);
    exit;
}

$time_in     = !empty($timeRow['time_in'])     ? $timeRow['time_in']     : "00:00:00";
$time_out    = !empty($timeRow['time_out'])    ? $timeRow['time_out']    : "00:00:00";
$nwt         = !empty($timeRow['nwt'])         ? $timeRow['nwt']         : "00:00:00";
$extra_break = !empty($timeRow['extra_break']) ? $timeRow['extra_break'] : "00:00:00";
$home        = !empty($timeRow['home'])        ? $timeRow['home']        : "00:00:00";
$night       = !empty($timeRow['night'])       ? $timeRow['night']       : "00:00:00";
$site_travel = !empty($timeRow['site_travel']) ? $timeRow['site_travel'] : "00:00:00";
$leave_hours = !empty($timeRow['leave_hours']) ? $timeRow['leave_hours'] : "00:00:00";
$work_status = $timeRow['work_status'];


if (timeToSeconds($time_in) > timeToSeconds($time_out)) {
    echo json_encode([
        "status" => false,
        "message" => "Duty end time must be greater than duty start time."
    ]);
    exit;
}


$net_seconds  = timeToSeconds($time_out) - timeToSeconds($time_in);
$net_seconds -= timeToSeconds($nwt);
$net_seconds -= timeToSeconds($extra_break);
$net_seconds += timeToSeconds($night);
$net_seconds += timeToSeconds($home);
$net_seconds += timeToSeconds($leave_hours);
$net_seconds -= timeToSeconds($site_travel);

if ($work_status !== "holiday" && $net_seconds < 0) {
    echo json_encode([
        "status" => false,
        "message" => "Net time should be greater than 0.",
        "net_time" => secondsToTime($net_seconds)
    ]);
    exit;
}

// 2. Calculate total_job (sum of all act_time)
$stmtActTime = $mysqli->prepare("
    SELECT COALESCE(SUM(TIME_TO_SEC(act_time)), 0) AS total_act_seconds
    FROM   tbl_workreports
    WHERE  user_id     = ?
      AND  date_report = ?
");
$stmtActTime->bind_param("is", $userId, $date_report);
$stmtActTime->execute();
$actTimeRow = $stmtActTime->get_result()->fetch_assoc();
$stmtActTime->close();

$total_job_seconds = (int) $actTimeRow['total_act_seconds'];
$total_job         = secondsToTime($total_job_seconds);

// 3. Validate total_job time
if ($total_job_seconds > $net_seconds) {
    echo json_encode([
        "status" => false,
        "message" => "Invalid Total Job Time! Job time exceeds net time.",
        "total_job" => $total_job,
        "net_time" => secondsToTime($net_seconds)
    ]);
    exit;
}

// 4. Mark tbl_time as complete
$netTime  = secondsToTime($net_seconds);
$stmtTime = $mysqli->prepare("
    UPDATE tbl_time
    SET    is_complete     = 1,
           completion_time = ?, 
           net_time = ?, 
           total_job = ?,
           is_bulk = 3
    WHERE  user_id = ?
      AND  date_log = ?
");
$stmtTime->bind_param("sssis", $datetime, $netTime, $total_job, $userId, $date_report);


if ($stmtTime->execute()) {
    // 5. Carry forward incomplete tasks (reusing results from validation query)
    if (!empty($cfRows)) {
        $stmtInsert = $mysqli->prepare("
            INSERT INTO tbl_workreports (
                user_id, date_report, taskname, main_type, job_type, 
                job_no, status, target_date, is_carry, delegation_id
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?)
        ");

        foreach ($cfRows as $row) {
            $stmtInsert->bind_param(
                "isssssssi",
                $userId,
                $row['cf_date'],
                $row['taskname'],
                $row['main_type'],
                $row['job_type'],
                $row['job_no'],
                $row['status'],
                $row['target_date'],
                $row['delegation_id']
            );
            $stmtInsert->execute();
        }
        $stmtInsert->close();
    }



    //6.Add routine jobs
    $sqlRoutine="SELECT j.id,
    j.job_name, j.main_type, j.sub_type,
    COALESCE(s.est_time, j.est_time) AS est_time, COALESCE(s.act_time, NULL) AS act_time, s.remarks
    FROM tbl_daily_jobs j 
    LEFT JOIN tbl_daily_job_status s ON s.job_id = j.id AND s.job_date = ?
    WHERE j.user_id=? AND j.status=1 AND j.auth_status=1";

    $stmtRoutine = $mysqli->prepare($sqlRoutine);
    $stmtRoutine->bind_param("si", $date_report, $userId);
    $stmtRoutine->execute();
    $routineResult = $stmtRoutine->get_result();
    $routineRows = $routineResult->fetch_all(MYSQLI_ASSOC);

    //insert routine jobs into tbl_workreports
    $stmtInsertRoutine = $mysqli->prepare("
        INSERT INTO tbl_workreports (
            user_id, date_report, taskname, main_type,
            job_no, est_time, act_time, description, status,
            job_type, is_carry
        )
        VALUES (?, ?, ?, ?, '', ?, ?, ?, 100, ?, 3)
    ");

    $stmtCheckRoutine = $mysqli->prepare("
        SELECT workreport_id
        FROM tbl_workreports
        WHERE user_id = ?
          AND date_report = ?
          AND taskname = ?
          AND main_type = ?
          AND job_type = ?
          AND status = 100
          AND is_carry = 3
        LIMIT 1
    ");


    foreach ($routineRows as $routineRow) {
        $taskname = trim($routineRow['job_name'] ?? '');
        if ($taskname === '') {
            continue;
        }

        $mainType = trim((string)($routineRow['main_type'] ?? ''));
        $jobType = trim((string)($routineRow['sub_type'] ?? ''));
        $estTime = trim($routineRow['est_time'] ?? '00:00:00');
        $actTime = trim($routineRow['act_time'] ?? '00:00:00');
        $remarks = trim($routineRow['remarks'] ?? '');

        if ($estTime === '') {
            $estTime = '00:00:00';
        }
        if ($actTime === '') {
            $actTime = '00:00:00';
        }

        // Block duplicate routine entries for the same completion date.
        $stmtCheckRoutine->bind_param(
            "issss",
            $userId,
            $date_report,
            $taskname,
            $mainType,
            $jobType
        );
        $stmtCheckRoutine->execute();
        $exists = $stmtCheckRoutine->get_result()->fetch_assoc();
        if ($exists) {
            continue;
        }

        $stmtInsertRoutine->bind_param(
            "isssssss",
            $userId,
            $date_report,
            $taskname,
            $mainType,
            $estTime,
            $actTime,
            $remarks,
            $jobType
        );
        $stmtInsertRoutine->execute();
    }
    $stmtCheckRoutine->close();
    $stmtInsertRoutine->close();

    echo json_encode([
        "status" => true,
        "message" => "Job diary completed successfully.",
        "net_time" => $netTime,
        "total_job" => $total_job
    ]);
} else {
    echo json_encode([
        "status" => false,
        "message" => "Failed to complete job diary: " . $stmtTime->error
    ]);
}
$stmtTime->close();

function timeToSeconds($time) {
    if (empty($time)) {
        $time = "00:00:00";
    }
    if (substr_count($time, ':') === 1) {
        $time .= ":00";
    }
    $parts = explode(':', $time);
    $parts = array_pad($parts, 3, 0);
    list($hours, $minutes, $seconds) = $parts;
    return ((int)$hours * 3600) + ((int)$minutes * 60) + (int)$seconds;
}

function secondsToTime($seconds) {
    $hours   = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs    = $seconds % 60;
    return sprintf("%02d:%02d:%02d", $hours, $minutes, $secs);
}
