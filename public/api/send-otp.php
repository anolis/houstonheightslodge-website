<?php
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'None');
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_domain', '.houstonheightslodge225.com');
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$body  = json_decode(file_get_contents('php://input'), true);
$email = strtolower(trim($body['email'] ?? ''));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email address.']);
    exit;
}

require __DIR__ . '/_config.php';
require __DIR__ . '/_gas.php';

// Rate limit: max OTP_MAX_SENDS per 15-minute window, tracked in session
$rateKey = 'otp_sends_' . md5($email);
$now     = time();
$sends   = array_filter($_SESSION[$rateKey] ?? [], fn($t) => $t > $now - 900);
if (count($sends) >= OTP_MAX_SENDS) {
    http_response_code(429);
    echo json_encode(['error' => 'Too many requests. Please wait before requesting another code.']);
    exit;
}
$sends[] = $now;
$_SESSION[$rateKey] = array_values($sends);

// Delegate OTP generation, storage, and email delivery to GAS
$result = gasPost(['action' => 'sendOtp', 'email' => $email, 'secret' => GAS_SECRET]);

if (!empty($result['error'])) {
    http_response_code(500);
    echo json_encode(['error' => $result['error']]);
    exit;
}

echo json_encode(['ok' => true]);
