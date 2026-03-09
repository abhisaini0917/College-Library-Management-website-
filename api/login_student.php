<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    send_response(false, 'Invalid request method');
}

// Get POST data
$email = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

// Validate inputs
if (empty($email) || empty($password)) {
    send_response(false, 'Email and password are required');
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    send_response(false, 'Invalid email format');
}

// Check if student exists in database
$stmt = $conn->prepare("SELECT id, first_name, last_name, email, password_hash, status FROM students WHERE email = ?");
if (!$stmt) {
    send_response(false, 'Database error: ' . $conn->error);
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    send_response(false, 'Invalid email or password');
}

$student = $result->fetch_assoc();
$stmt->close();

// Verify password
// TODO: re-enable hashing before going live
// if (!password_verify($password, $student['password_hash'])) {
if ($password !== $student['password_hash']) {
    send_response(false, 'Invalid email or password');
}

// Check approval status
if (isset($student['status'])) {
    if ($student['status'] === 'pending') {
        send_response(false, 'Your account is pending admin approval.');
    } elseif ($student['status'] === 'declined') {
        send_response(false, 'Your registration has been declined by the admin.');
    }
}

// Create session
$_SESSION['user_id'] = $student['id'];
$_SESSION['user_type'] = 'student';
$_SESSION['user_name'] = $student['first_name'] . ' ' . $student['last_name'];
$_SESSION['user_email'] = $student['email'];
$_SESSION['login_time'] = time();

// Update last login
$update_stmt = $conn->prepare("UPDATE students SET last_login = NOW() WHERE id = ?");
$update_stmt->bind_param("i", $student['id']);
$update_stmt->execute();
$update_stmt->close();

send_response(true, 'Login successful', [
    'user_id' => $student['id'],
    'user_name' => $_SESSION['user_name'],
    'user_type' => 'student',
    'redirect' => '../student_dashboard.html'
]);

$conn->close();
?>
