<?php
require_once __DIR__ . '/vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Secret key - store this in a .env file or config in production
define('JWT_SECRET', 'AriesHRMS@2024#xK9$mP2&qL7!nR4@zWq8^vYt1');
define('JWT_ALGO', 'HS256');
//define('JWT_EXPIRY', 3600); // 1 hour in seconds
define('JWT_EXPIRY', 2592000); // 30 days

function generateToken(array $payload): string {
    $now = time();
    $userId = $payload['user_id'];
    $tokenPayload = [
        'iat'   => $now,               // Issued at
        'exp'   => $now + JWT_EXPIRY,  // Expiry
        'uid'   => $userId,
        'uname' => $payload['username'],
        'usertype' => DecideUserType($userId),
        'timezone' => $payload['timezone']
    ];
    return JWT::encode($tokenPayload, JWT_SECRET, JWT_ALGO);
}

function requireAuth(): object {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
        http_response_code(401);
        echo json_encode([
            "status"  => false,
            "authStatus"=> false,
            "message" => "Authorization token required"
        ]);
        exit;
    }

    $token = trim(substr($authHeader, 7)); 

    try {
        $decoded = JWT::decode($token, new Key(JWT_SECRET, JWT_ALGO));
        return $decoded;

    } catch (\Firebase\JWT\ExpiredException $e) {
        http_response_code(401);
        echo json_encode([
            "status"  => false,
            "authStatus"=> false,
            "message" => "Token has expired"
        ]);
        exit;

    } catch (\Firebase\JWT\SignatureInvalidException $e) {
        http_response_code(401);
        echo json_encode([
            "status"  => false,
            "authStatus"=> false,
            "message" => "Invalid token signature"
        ]);
        exit;

    } catch (\Exception $e) {
        http_response_code(401);
        echo json_encode([
            "status"  => false,
            "authStatus"=> false,
            "message" => "Invalid or malformed token"
        ]);
        exit;
    }
}

function getTimezone(object $auth): string {
    return !empty($auth->timezone) ? $auth->timezone : 'Asia/Kolkata';
}

function DecideUserType($userId) {
    global $mysqli;
    $stmt = $mysqli->prepare("SELECT is_regular, emp_division_id FROM tbl_users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if ($row) {
        $isRegular = $row['is_regular'];
        $empDivisionId = $row['emp_division_id'];

        if ($isRegular == "2" && $empDivisionId == "3") {
            return 3;
        }
        return $isRegular;
    }
    return 0;
}