<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';

// Verify admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    send_response(false, 'Unauthorized. Admin access required.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    send_response(false, 'Invalid request method');
}

$stmt = $conn->prepare("SELECT id, first_name, last_name, email, roll_no, department, semester, created_at, status FROM students WHERE status = 'pending' ORDER BY created_at DESC");
if (!$stmt) {
    send_response(false, 'Database error: ' . $conn->error);
}

$stmt->execute();
$result = $stmt->get_result();

$pending_students = [];
while ($row = $result->fetch_assoc()) {
    $pending_students[] = $row;
}
$stmt->close();

send_response(true, 'Pending students fetched successfully', [
    'students' => $pending_students
]);
$conn->close();
?>
