<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student') {
    http_response_code(401);
    send_response(false, 'Unauthorized access');
}

$user_id = $_SESSION['user_id'];

// Check if request method is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    send_response(false, 'Invalid request method');
}

$stmt = $conn->prepare("SELECT id, first_name, last_name, email, roll_no, department, semester, profile_picture, status, created_at, last_login FROM students WHERE id = ?");

if (!$stmt) {
    http_response_code(500);
    send_response(false, 'Database error: ' . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    send_response(false, 'Student profile not found');
}

$student = $result->fetch_assoc();
$stmt->close();

send_response(true, 'Profile fetched successfully', $student);

$conn->close();
?>
