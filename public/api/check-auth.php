<?php

ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'None');
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_domain', '.houstonheightslodge225.com');
session_start();

header('Content-Type: application/json');
echo json_encode(['authenticated' => ! empty($_SESSION['members_authenticated'])]);
