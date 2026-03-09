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

// Step 1: Fetch the record to delete associated files (images, PDFs)
$stmt = $conn->prepare("SELECT * FROM $table WHERE id = ?");
if (!$stmt) {
    send_response(false, 'Database error preparing select: ' . $conn->error);
}

$stmt->bind_param("i", $book_id);
$stmt->execute();
$result = $stmt->get_result();
$book_data = $result->fetch_assoc();
$stmt->close();

if (!$book_data) {
    send_response(false, 'Book not found.');
}

// Step 2: Delete from Database
$del_stmt = $conn->prepare("DELETE FROM $table WHERE id = ?");
if (!$del_stmt) {
    send_response(false, 'Database error preparing delete: ' . $conn->error);
}

$del_stmt->bind_param("i", $book_id);

if ($del_stmt->execute()) {
    $del_stmt->close();

    // Step 3: Clean up files from filesystem if database deletion succeeded
    $base_dir = __DIR__ . '/../';
    
    // Delete Cover Image if exists
    $image_col = isset($book_data['book_image']) ? $book_data['book_image'] : (isset($book_data['image']) ? $book_data['image'] : null);
    if (!empty($image_col) && file_exists($base_dir . $image_col)) {
        @unlink($base_dir . $image_col);
    }

    // Delete associated document file (if it's an online book)
    if ($book_type === 'online') {
        $file_col = isset($book_data['file_url']) ? $book_data['file_url'] : null;
        if (!empty($file_col) && file_exists($base_dir . $file_col)) {
            @unlink($base_dir . $file_col);
        }
    }

    send_response(true, 'Book successfully deleted.', ['id' => $book_id, 'type' => $book_type]);
} else {
    send_response(false, 'Database error executing delete: ' . $del_stmt->error);
}

$conn->close();
?>
