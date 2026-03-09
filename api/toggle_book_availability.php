<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(401);
    send_response(false, 'Unauthorized. Admin access required.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    send_response(false, 'Invalid request method.');
}

$book_id = isset($_POST['book_id']) ? intval($_POST['book_id']) : 0;
$book_type = isset($_POST['book_type']) ? trim($_POST['book_type']) : '';
$status = isset($_POST['status']) ? trim($_POST['status']) : '1';

if ($book_id <= 0 || !in_array($book_type, ['offline', 'online'])) {
    send_response(false, 'Invalid book ID or type specified.');
}

$table = $book_type === 'offline' ? 'book_offline' : 'book_online';
$status_col = 'availability';

// Check if column exists, some schemas use 'status' or 'is_available' instead of 'availability'
// We assume 'availability' is the primary column based on frontend parsing
$stmt = $conn->prepare("UPDATE $table SET $status_col = ? WHERE id = ?");
if (!$stmt) {
    send_response(false, 'Database error: ' . $conn->error);
}

$stmt->bind_param("si", $status, $book_id);

if ($stmt->execute()) {
    send_response(true, 'Book availability updated successfully.');
} else {
    send_response(false, 'Failed to update book availability: ' . $stmt->error);
}

$stmt->close();
$conn->close();
?>
