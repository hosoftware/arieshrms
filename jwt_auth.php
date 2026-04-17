<?php
require_once __DIR__ . '/vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Secret key - store this in a .env file or config in production
define('JWT_SECRET', 'AriesHRMS@2024#xK9$mP2&qL7!nR4@zWq8^vYt');
define('JWT_ALGO', 'HS256');
//define('JWT_EXPIRY', 3600); // 1 hour in seconds
define('JWT_EXPIRY', 2592000); // 30 days

function generateToken(array $payload): string {
    $now = time();
    $tokenPayload = [
        'iat'   => $now,               // Issued at
        'exp'   => $now + JWT_EXPIRY,  // Expiry
        'uid'   => $payload['user_id'],
        'uname' => $payload['username'],
        'timezone' => $payload[trim('timezone')],
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
            "message" => "Token has expired"
        ]);
        exit;

    } catch (\Firebase\JWT\SignatureInvalidException $e) {
        http_response_code(401);
        echo json_encode([
            "status"  => false,
            "message" => "Invalid token signature"
        ]);
        exit;

    } catch (\Exception $e) {
        http_response_code(401);
        echo json_encode([
            "status"  => false,
            "message" => "Invalid or malformed token"
        ]);
        exit;
    }
}

function getTimezone(object $auth): string {
    return !empty($auth->timezone) ? $auth->timezone : 'Asia/Kolkata';
}