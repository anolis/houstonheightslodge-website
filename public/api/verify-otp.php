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

require __DIR__ . '/_config.php';
require __DIR__ . '/_gas.php';

$body  = json_decode(file_get_contents('php://input'), true);
$email = strtolower(trim($body['email'] ?? ''));
$otp   = trim($body['otp'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^\d{6}$/', $otp)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request.']);
    exit;
}

// Ask GAS to verify the code against the OTPs sheet
$result = gasPost(['action' => 'verifyOtp', 'email' => $email, 'code' => $otp, 'secret' => GAS_SECRET]);

if (!empty($result['ok'])) {
    $_SESSION['members_authenticated'] = true;
    $_SESSION['members_email']         = $email;
    echo json_encode(['ok' => true]);
} else {
    http_response_code(401);
    echo json_encode(['error' => $result['error'] ?? 'Invalid code. Please try again.']);
}
