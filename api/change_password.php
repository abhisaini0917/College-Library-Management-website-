<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';

// Must be logged in as a student
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student') {
    http_response_code(401);
    send_response(false, 'Unauthorized. Please log in first.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    send_response(false, 'Invalid request method.');
}

$current_password  = isset($_POST['current_password'])  ? $_POST['current_password']  : '';
$new_password      = isset($_POST['new_password'])       ? $_POST['new_password']       : '';
$confirm_password  = isset($_POST['confirm_password'])   ? $_POST['confirm_password']   : '';

if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    send_response(false, 'All fields are required.');
}

if (strlen($new_password) < 6) {
    send_response(false, 'New password must be at least 6 characters.');
}

if ($new_password !== $confirm_password) {
    send_response(false, 'New password and confirm password do not match.');
}

$user_id = $_SESSION['user_id'];

// Fetch current password hash from DB
$stmt = $conn->prepare("SELECT password_hash FROM students WHERE id = ?");
if (!$stmt) send_response(false, 'Database error: ' . $conn->error);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    send_response(false, 'Student not found.');
}

$student = $result->fetch_assoc();
$stmt->close();

// Verify current password
// TODO: When hashing is enabled, change this to password_verify($current_password, $student['password_hash'])
if ($current_password !== $student['password_hash']) {
    send_response(false, 'Current password is incorrect.');
}

// Update new password
// TODO: When hashing is enabled, use password_hash($new_password, PASSWORD_BCRYPT)
$new_hash = $new_password;

$update = $conn->prepare("UPDATE students SET password_hash = ? WHERE id = ?");
if (!$update) send_response(false, 'Database error: ' . $conn->error);
$update->bind_param("si", $new_hash, $user_id);

if ($update->execute()) {
    $update->close();
    send_response(true, 'Password changed successfully.');
} else {
    send_response(false, 'Failed to update password: ' . $update->error);
}

$conn->close();
?>
