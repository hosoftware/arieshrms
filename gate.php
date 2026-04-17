<?php
require_once __DIR__ . '/connect.inc.php';
require_once __DIR__ . '/phpqrcode/qrlib.php';

function generateRotatingToken(): string {
    $window = floor(time() / VALIDITY_SECONDS);
    $secret = QR_HMAC_SECRET;
    $hash   = hash_hmac('sha256', (string)$window, $secret);
    return substr($hash, 0, 16);
}

$ip              = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$datetime        = date("Y-m-d H:i:s");
$validitySeconds = VALIDITY_SECONDS;


$deactivateStmt = $mysqli->prepare(
    "UPDATE tbl_breaktime_qr 
     SET is_active = 0 
     WHERE is_active = 1 
       AND TIMESTAMPDIFF(SECOND, created_at, ?) >= ?"
);
$deactivateStmt->bind_param("si", $datetime, $validitySeconds);
$deactivateStmt->execute();
$deactivateStmt->close();

$fetchStmt = $mysqli->prepare(
    "SELECT qr_token, created_at,
        GREATEST(0, ? - TIMESTAMPDIFF(SECOND, created_at, ?)) AS remaining_seconds
     FROM tbl_breaktime_qr
     WHERE is_active = 1
     ORDER BY created_at DESC
     LIMIT 1"
);
$fetchStmt->bind_param("is", $validitySeconds, $datetime);
$fetchStmt->execute();
$activeRow = $fetchStmt->get_result()->fetch_assoc();
$fetchStmt->close();

if ($activeRow) {
    $token       = $activeRow['qr_token'];
    $remainingMs = max(1, (int)$activeRow['remaining_seconds']) * 1000;
} else {
    $token = generateRotatingToken();

    $insertStmt = $mysqli->prepare(
        "INSERT INTO tbl_breaktime_qr (qr_token, is_active, created_at, ip_address)
         VALUES (?, 1, ?, ?)"
    );
    $insertStmt->bind_param("sss", $token, $datetime, $ip);
    $insertStmt->execute();
    $insertStmt->close();

    $remainingMs = $validitySeconds * 1000;
}

// Step 3: Generate QR code image from the current active token
$tmpFile = tempnam(sys_get_temp_dir(), 'qr_') . '.png';
try {
    QRcode::png($token, $tmpFile, 'L', 8, 2);
    $qrBase64 = base64_encode(file_get_contents($tmpFile));
} finally {
    if (file_exists($tmpFile)) {
        unlink($tmpFile);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Breaktime QR Code</title>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: #0f172a;
        font-family: 'Segoe UI', sans-serif;
        color: #fff;
    }

    .card {
        background: #1e293b;
        border-radius: 24px;
        padding: 60px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 32px;
        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.4);
        border: 1px solid #334155;
    }

    .title {
        font-size: 36px;
        font-weight: 700;
        color: #e2e8f0;
        letter-spacing: 0.5px;
    }

    .qr-wrapper {
        background: #fff;
        padding: 24px;
        border-radius: 20px;
    }

    .qr-wrapper img {
        display: block;
        width: 500px;
        height: 500px;
        image-rendering: pixelated;
    }

    .timer-wrapper {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        width: 100%;
    }

    .timer-label {
        font-size: 14px;
        color: #94a3b8;
        letter-spacing: 1px;
        text-transform: uppercase;
    }

    .timer-display {
        font-size: 48px;
        font-weight: 800;
        font-variant-numeric: tabular-nums;
        color: #38bdf8;
        letter-spacing: 2px;
    }

    .timer-display.warning {
        color: #f59e0b;
    }

    .timer-display.danger {
        color: #ef4444;
    }

    .progress-bar-bg {
        width: 100%;
        height: 8px;
        background: #334155;
        border-radius: 99px;
        overflow: hidden;
    }

    .progress-bar-fill {
        height: 100%;
        border-radius: 99px;
        background: #38bdf8;
        transition: width 1s linear, background 1s;
    }
    </style>
</head>

<body>
    <div class="card">
        <div class="title">Breaktime QR Code</div>

        <div class="qr-wrapper">
            <img src="data:image/png;base64,<?= $qrBase64 ?>" alt="QR Code">
        </div>

        <div class="timer-wrapper">
            <div class="timer-label">Refreshes in</div>
            <div class="timer-display" id="timerDisplay">--:--</div>
            <div class="progress-bar-bg">
                <div class="progress-bar-fill" id="progressBar" style="width:100%"></div>
            </div>
        </div>
    </div>

    <script>
    const totalMs = <?= $remainingMs ?>;
    const totalSecs = Math.floor(totalMs / 1000);
    let remaining = totalSecs;

    const display = document.getElementById('timerDisplay');
    const bar = document.getElementById('progressBar');

    function pad(n) {
        return String(n).padStart(2, '0');
    }

    function updateTimer() {
        if (remaining <= 0) {
            location.reload();
            return;
        }

        const mins = Math.floor(remaining / 60);
        const secs = remaining % 60;
        display.textContent = pad(mins) + ':' + pad(secs);

        const pct = (remaining / totalSecs) * 100;
        bar.style.width = pct + '%';

        display.classList.remove('warning', 'danger');
        bar.style.background = '#38bdf8';

        if (remaining <= 30) {
            display.classList.add('danger');
            bar.style.background = '#ef4444';
        } else if (remaining <= 60) {
            display.classList.add('warning');
            bar.style.background = '#f59e0b';
        }

        remaining--;
    }

    updateTimer();
    setInterval(updateTimer, 1000);
    </script>
</body>

</html>