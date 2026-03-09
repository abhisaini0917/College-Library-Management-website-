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

if ($book_id <= 0 || !in_array($book_type, ['offline', 'online'])) {
    send_response(false, 'Invalid book ID or type specified.');
}

$table = $book_type === 'offline' ? 'book_offline' : 'book_online';

// Collect common fields
$title = trim($_POST['title'] ?? '');
$author = trim($_POST['author'] ?? '');
$category = trim($_POST['category'] ?? '');
$isbn = trim($_POST['isbn'] ?? '');
$description = trim($_POST['description'] ?? '');

$sql = "UPDATE $table SET title = ?, author = ?, category = ?, isbn = ?, description = ?";
$params = [$title, $author, $category, $isbn, $description];
$types = "sssss";

// Offline books also have total_copies, availability, and a shelf_location
if ($book_type === 'offline') {
    $total_copies = intval($_POST['total_copies'] ?? 1);
    $availability = trim($_POST['availability'] ?? '1');
    $shelf_location = trim($_POST['shelf_location'] ?? '');
    
    $sql .= ", total_copies = ?, availability = ?, shelf_location = ?";
    $params[] = $total_copies;
    $params[] = $availability;
    $params[] = $shelf_location;
    $types .= "iss";
}

$sql .= " WHERE id = ?";
$params[] = $book_id;
$types .= "i";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    send_response(false, 'Database preparation error: ' . $conn->error);
}

// Dynamically bind parameters
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    send_response(true, 'Book details updated successfully.');
} else {
    send_response(false, 'Failed to update book: ' . $stmt->error);
}

$stmt->close();
$conn->close();
?>
