<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    send_response(false, 'Invalid request method');
}

$roll_no           = isset($_POST['roll_no']) ? sanitize_input($_POST['roll_no']) : '';
$first_name        = isset($_POST['first_name']) ? sanitize_input($_POST['first_name']) : '';
$security_question = isset($_POST['security_question']) ? sanitize_input($_POST['security_question']) : '';
$security_answer   = isset($_POST['security_answer']) ? sanitize_input($_POST['security_answer']) : '';

if (empty($roll_no) || empty($first_name) || empty($security_question) || empty($security_answer)) {
    send_response(false, 'All fields are required');
}

// Find the student by roll no
$stmt = $conn->prepare("SELECT id, first_name, security_question, security_answer FROM students WHERE roll_no = ?");
if (!$stmt) {
    send_response(false, 'Database error: ' . $conn->error);
}

$stmt->bind_param("s", $roll_no);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    send_response(false, 'Verification failed. Details do not match our records.');
}

$student = $result->fetch_assoc();
$stmt->close();

// Check if first name matches (case-insensitive)
if (strtolower($student['first_name']) !== strtolower($first_name)) {
    send_response(false, 'Verification failed. Details do not match our records.');
}

// Check if security question matches
if ($student['security_question'] !== $security_question) {
    send_response(false, 'Verification failed. Incorrect security question or answer.');
}

// Check if security answer matches (case-insensitive string comparison)
if (strtolower($student['security_answer']) !== strtolower($security_answer)) {
    send_response(false, 'Verification failed. Incorrect security question or answer.');
}

// Everything matches! Set a temporary session to allow password reset
$_SESSION['reset_roll_no'] = $roll_no;
$_SESSION['reset_expires'] = time() + (15 * 60); // 15 minutes expiration

send_response(true, 'Verification successful. You may now reset your password.');

$conn->close();
?>
