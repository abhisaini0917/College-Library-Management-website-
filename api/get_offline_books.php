<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';

// Must be logged in as either a student or an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'student' && $_SESSION['user_type'] !== 'admin')) {
    http_response_code(401);
    send_response(false, 'Unauthorized. Please log in first.');
}

// Fetch all columns from book_offline
$stmt = $conn->prepare("SELECT * FROM book_offline ORDER BY id DESC");
if (!$stmt) {
    send_response(false, 'Database error: ' . $conn->error);
}

$stmt->execute();
$result = $stmt->get_result();
$books  = [];

while ($row = $result->fetch_assoc()) {
    $books[] = $row;
}

$stmt->close();
$conn->close();

send_response(true, 'Offline books fetched successfully', $books);
?>
