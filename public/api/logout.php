<?php

ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'None');
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_domain', '.houstonheightslodge225.com');
session_start();

unset($_SESSION['members_authenticated'], $_SESSION['members_email']);

header('Content-Type: application/json');
echo json_encode(['ok' => true]);
