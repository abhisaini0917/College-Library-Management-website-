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

$title       = trim($_POST['title']       ?? '');
$author      = trim($_POST['author']      ?? '');
$category    = trim($_POST['category']    ?? '');
$file_type   = trim($_POST['file_type']   ?? 'PDF');
$description = trim($_POST['description'] ?? '');

if (!$title || !$author) {
    send_response(false, 'Title and Author are required.');
}

// Handle book file upload (instead of URL)
$file_url = '';
if (!empty($_FILES['book_file']['name']) && $_FILES['book_file']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/../uploads/files/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    
    $ext = strtolower(pathinfo($_FILES['book_file']['name'], PATHINFO_EXTENSION));
    $allowed = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt'];
    
    if (!in_array($ext, $allowed)) {
        send_response(false, 'Invalid file type. Allowed: PDF, DOC, DOCX, PPT, PPTX, TXT.');
    }
    
    $filename = 'doc_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
    if (move_uploaded_file($_FILES['book_file']['tmp_name'], $uploadDir . $filename)) {
        $file_url = 'uploads/files/' . $filename;
    } else {
        send_response(false, 'Failed to upload document file.');
    }
} else {
    send_response(false, 'Book file is required.');
}

// Handle optional book image upload
$book_image = '';
if (!empty($_FILES['book_image']['name']) && $_FILES['book_image']['error'] === UPLOAD_ERR_OK) {
    $imgUploadDir = __DIR__ . '/../uploads/books/';
    if (!is_dir($imgUploadDir)) mkdir($imgUploadDir, 0755, true);
    $imgExt     = strtolower(pathinfo($_FILES['book_image']['name'], PATHINFO_EXTENSION));
    $imgAllowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (in_array($imgExt, $imgAllowed)) {
        $imgFilename = 'book_' . time() . '_' . rand(1000, 9999) . '.' . $imgExt;
        if (move_uploaded_file($_FILES['book_image']['tmp_name'], $imgUploadDir . $imgFilename)) {
            $book_image = 'uploads/books/' . $imgFilename;
        }
    }
}

// 7 columns -> type string: s s s s s s s 
$stmt = $conn->prepare(
    "INSERT INTO book_online (title, author, category, file_type, file_url, description, book_image)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);
if (!$stmt) {
    send_response(false, 'Query prepare error: ' . $conn->error);
}

$stmt->bind_param('sssssss', $title, $author, $category, $file_type, $file_url, $description, $book_image);

if ($stmt->execute()) {
    send_response(true, 'Online book added successfully.');
} else {
    send_response(false, 'Failed to add book: ' . $stmt->error);
}
$stmt->close();
$conn->close();
?>
