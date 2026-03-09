<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';

// Verify admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    send_response(false, 'Unauthorized. Admin access required.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    send_response(false, 'Invalid request method');
}

$student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
$new_status = isset($_POST['status']) ? sanitize_input($_POST['status']) : '';

if ($student_id <= 0 || !in_array($new_status, ['approved', 'declined'])) {
    send_response(false, 'Invalid input parameters.');
}

$stmt = $conn->prepare("UPDATE students SET status = ? WHERE id = ? AND status = 'pending'");
if (!$stmt) {
    send_response(false, 'Database error: ' . $conn->error);
}

$stmt->bind_param("si", $new_status, $student_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        send_response(true, "Student registration has been {$new_status}.", [
            'student_id' => $student_id,
            'status' => $new_status
        ]);
    } else {
        send_response(false, "Student not found or status is no longer pending.");
    }
} else {
    send_response(false, 'Error updating status: ' . $stmt->error);
}

$stmt->close();
$conn->close();
?>
