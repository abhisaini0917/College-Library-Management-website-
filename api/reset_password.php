<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    send_response(false, 'Invalid request method');
}

// Require the user to have passed verification
if (!isset($_SESSION['reset_roll_no']) || !isset($_SESSION['reset_expires'])) {
    send_response(false, 'Unauthorized access. Please verify your identity first.');
}

// Check if verification session expired (15 mins)
if (time() > $_SESSION['reset_expires']) {
    unset($_SESSION['reset_roll_no']);
    unset($_SESSION['reset_expires']);
    send_response(false, 'Session expired. Please verify your identity again.');
}

$password = isset($_POST['password']) ? $_POST['password'] : '';
$confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

if (empty($password) || empty($confirm_password)) {
    send_response(false, 'Both password fields are required.');
}

if (strlen($password) < 6) {
    send_response(false, 'Password must be at least 6 characters.');
}

if ($password !== $confirm_password) {
    send_response(false, 'Passwords do not match.');
}

$roll_no = $_SESSION['reset_roll_no'];

// Update the password in db
// TODO: When password hashing is enabled in registration, enable it here too.
// $password_hash = password_hash($password, PASSWORD_BCRYPT);
$password_hash = $password;

$stmt = $conn->prepare("UPDATE students SET password_hash = ? WHERE roll_no = ?");
if (!$stmt) {
    send_response(false, 'Database error: ' . $conn->error);
}

$stmt->bind_param("ss", $password_hash, $roll_no);

if ($stmt->execute()) {
    // Clear the reset session data so it can't be reused
    unset($_SESSION['reset_roll_no']);
    unset($_SESSION['reset_expires']);
    send_response(true, 'Password has been successfully reset. You can now log in.');
} else {
    send_response(false, 'Failed to update password: ' . $stmt->error);
}

$stmt->close();
$conn->close();
?>
