<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

// config.php handles the actual expiration and will exit with 401 if expired.
// If it gets here, the session is still valid.

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    send_response(false, 'Not logged in', ['session_expired' => true]);
}

send_response(true, 'Session active');
?>
