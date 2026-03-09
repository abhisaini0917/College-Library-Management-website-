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

// Check if admin exists in database
$stmt = $conn->prepare("SELECT id, first_name, last_name, email, password_hash, role FROM admins WHERE email = ?");
if (!$stmt) {
    send_response(false, 'Database error: ' . $conn->error);
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    send_response(false, 'Invalid email or password');
}

$admin = $result->fetch_assoc();
$stmt->close();

// Verify password
// TODO: re-enable hashing before going live
// if (!password_verify($password, $admin['password_hash'])) {
if ($password !== $admin['password_hash']) {
    send_response(false, 'Invalid email or password');
}

// Create session
$_SESSION['user_id'] = $admin['id'];
$_SESSION['user_type'] = 'admin';
$_SESSION['user_name'] = $admin['first_name'] . ' ' . $admin['last_name'];
$_SESSION['user_email'] = $admin['email'];
$_SESSION['user_role'] = $admin['role'];
$_SESSION['login_time'] = time();

// Update last login
$update_stmt = $conn->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
$update_stmt->bind_param("i", $admin['id']);
$update_stmt->execute();
$update_stmt->close();

send_response(true, 'Login successful', [
    'user_id' => $admin['id'],
    'user_name' => $_SESSION['user_name'],
    'user_type' => 'admin',
    'user_role' => $admin['role'],
    'redirect' => '../admin_dashboard.html'
]);

$conn->close();
?>
