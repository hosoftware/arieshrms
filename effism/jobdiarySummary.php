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

// 1. Fetch net time from tbl_time
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
        "status"  => false,
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

$net_seconds  = timeToSeconds($time_out) - timeToSeconds($time_in);
$net_seconds -= timeToSeconds($nwt);
$net_seconds -= timeToSeconds($extra_break);
$net_seconds += timeToSeconds($night);
$net_seconds += timeToSeconds($home);
$net_seconds += timeToSeconds($leave_hours);
$net_seconds -= timeToSeconds($site_travel);

$net_time = ($work_status === "holiday" || $net_seconds < 0)
    ? "00:00:00"
    : secondsToTime($net_seconds);

// 2. Fetch total act_time and est_time from tbl_workreports
$stmtTotals = $mysqli->prepare("
    SELECT
        COALESCE(SUM(TIME_TO_SEC(act_time)), 0) AS total_act_seconds,
        COALESCE(SUM(TIME_TO_SEC(est_time)), 0) AS total_est_seconds
    FROM  tbl_workreports
    WHERE user_id     = ?
      AND date_report = ? AND is_carry!=3
");
$stmtTotals->bind_param("is", $userId, $date_report);
$stmtTotals->execute();
$totalsRow = $stmtTotals->get_result()->fetch_assoc();
$stmtTotals->close();


   $routine_time_result = $mysqli->query("select SUM(TIME_TO_SEC(if(status.id>0,status.est_time,daily.est_time))) as total_est,SUM(TIME_TO_SEC(status.act_time)) as total_act  from   tbl_daily_jobs daily left join tbl_daily_job_status status on daily.id=status.job_id and job_date='$date_report' where  daily.status=1 and daily.auth_status=1 and daily.user_id='$userId'");

                $routine_time_row = $routine_time_result->fetch_assoc();
                if(isset($routine_time_row)&&!empty($routine_time_row)){

                $routine_est_sec = $routine_time_row ['total_est'];

                $routine_act_sec = $routine_time_row ['total_act'];}
                else{
                  $routine_est_sec=0;
                  $routine_act_sec =0;
                }


$total_act = secondsToTime((int) $totalsRow['total_act_seconds']+$routine_act_sec);
$total_est = secondsToTime((int) $totalsRow['total_est_seconds']+$routine_est_sec);

// 3. Return all totals
echo json_encode([
    "status"    => true,
    "net_time"  => $net_time,   // Net working time for the day
    "total_act" => $total_act,  // Sum of all act_time entries
    "total_est" => $total_est,  // Sum of all est_time entries
]);
exit;


function timeToSeconds($time) {
    if (empty($time)) return 0;
    if (substr_count($time, ':') === 1) $time .= ":00";
    $parts = array_pad(explode(':', $time), 3, 0);
    return ((int)$parts[0] * 3600) + ((int)$parts[1] * 60) + (int)$parts[2];
}

function secondsToTime($seconds) {
    if ($seconds < 0) $seconds = 0;
    return sprintf("%02d:%02d:%02d",
        floor($seconds / 3600),
        floor(($seconds % 3600) / 60),
        $seconds % 60
    );
}