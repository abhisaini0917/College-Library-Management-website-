<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    send_response(false, 'Invalid request method');
}

$roll_no = isset($_POST['roll_no']) ? sanitize_input($_POST['roll_no']) : '';

if (empty($roll_no)) {
    send_response(false, 'Roll number is required');
}

$stmt = $conn->prepare("SELECT first_name, last_name, status, created_at FROM students WHERE roll_no = ?");
if (!$stmt) {
    send_response(false, 'Database error: ' . $conn->error);
}

$stmt->bind_param("s", $roll_no);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    send_response(false, 'No registration found for this roll number');
}

$student = $result->fetch_assoc();
$stmt->close();

send_response(true, 'Status retrieved successfully', [
    'first_name' => $student['first_name'],
    'last_name' => $student['last_name'],
    'status' => $student['status'],
    'created_at' => $student['created_at']
]);

$conn->close();
?>
