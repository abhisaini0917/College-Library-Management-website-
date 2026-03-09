<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    send_response(false, 'Unauthorized.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_response(false, 'Invalid request method.');
}

$title        = trim($_POST['title']          ?? '');
$author       = trim($_POST['author']         ?? '');
$category     = trim($_POST['category']       ?? '');
$isbn         = trim($_POST['isbn']           ?? '');
$total_copies   = intval($_POST['total_copies'] ?? 0);
$availability   = trim($_POST['availability']   ?? 'available');
$shelf_location = trim($_POST['shelf_location'] ?? '');
$description    = trim($_POST['description']    ?? '');

if (!$title || !$author) {
    send_response(false, 'Title and Author are required.');
}
if ($total_copies < 1) {
    send_response(false, 'Total copies must be at least 1.');
}

// Handle optional book image upload
$book_image = '';
if (!empty($_FILES['book_image']['name']) && $_FILES['book_image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/../uploads/books/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $ext     = strtolower(pathinfo($_FILES['book_image']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (in_array($ext, $allowed)) {
        $filename = 'book_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        if (move_uploaded_file($_FILES['book_image']['tmp_name'], $uploadDir . $filename)) {
            $book_image = 'uploads/books/' . $filename;
        }
    }
}

// 9 columns -> type string: s s s s i s s s s
$stmt = $conn->prepare(
    "INSERT INTO book_offline (title, author, category, isbn, total_copies, availability, shelf_location, description, book_image)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
if (!$stmt) {
    send_response(false, 'Query prepare error: ' . $conn->error);
}

$stmt->bind_param('ssssissss', $title, $author, $category, $isbn, $total_copies, $availability, $shelf_location, $description, $book_image);

if ($stmt->execute()) {
    send_response(true, 'Offline book added successfully.');
} else {
    send_response(false, 'Failed to add book: ' . $stmt->error);
}
$stmt->close();
$conn->close();
?>
