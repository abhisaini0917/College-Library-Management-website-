<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    send_response(false, 'Invalid request method');
}

// Get POST data
$first_name      = isset($_POST['first_name'])      ? sanitize_input($_POST['first_name'])  : '';
$last_name       = isset($_POST['last_name'])       ? sanitize_input($_POST['last_name'])   : '';
$email           = isset($_POST['email'])           ? sanitize_input($_POST['email'])       : '';
$password        = isset($_POST['password'])        ? $_POST['password']                    : '';
$confirm_password= isset($_POST['confirm_password'])? $_POST['confirm_password']            : '';
$roll_no         = isset($_POST['roll_no'])         ? sanitize_input($_POST['roll_no'])     : '';
$department      = isset($_POST['department'])      ? sanitize_input($_POST['department'])  : '';
$semester        = isset($_POST['semester'])        ? (int)$_POST['semester']               : 0;
$security_question= isset($_POST['security_question'])? sanitize_input($_POST['security_question']) : '';
$security_answer = isset($_POST['security_answer']) ? sanitize_input($_POST['security_answer'])   : '';
$user_type       = 'student';

// Validate required fields
if (empty($first_name) || empty($email) || empty($password) || empty($roll_no) || empty($department) || empty($security_question) || empty($security_answer) || $semester < 1) {
    send_response(false, 'All required fields must be filled');
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    send_response(false, 'Invalid email format');
}

// Validate password length
if (strlen($password) < 6) {
    send_response(false, 'Password must be at least 6 characters');
}

// Validate password match
if ($password !== $confirm_password) {
    send_response(false, 'Passwords do not match');
}

// Validate semester range
if ($semester < 1 || $semester > 6) {
    send_response(false, 'Invalid semester');
}

// Check if email already exists
$check = $conn->prepare("SELECT id FROM students WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    send_response(false, 'Email is already registered');
}
$check->close();

// Check if roll_no already exists
$check2 = $conn->prepare("SELECT id FROM students WHERE roll_no = ?");
$check2->bind_param("s", $roll_no);
$check2->execute();
if ($check2->get_result()->num_rows > 0) {
    send_response(false, 'Roll number is already registered');
}
$check2->close();

// Handle profile picture upload (optional)
$profile_picture = null;
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
    $file      = $_FILES['profile_picture'];
    $allowed   = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize   = 2 * 1024 * 1024; // 2 MB

    if (!in_array($file['type'], $allowed)) {
        send_response(false, 'Profile picture must be JPG, PNG, GIF, or WebP');
    }
    if ($file['size'] > $maxSize) {
        send_response(false, 'Profile picture must be smaller than 2 MB');
    }

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'student_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext;
    $uploadDir= __DIR__ . '/../uploads/students/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        send_response(false, 'Failed to upload profile picture');
    }
    $profile_picture = 'uploads/students/' . $filename;
}

// TODO: re-enable password hashing before going live
// $password_hash = password_hash($password, PASSWORD_BCRYPT);
$password_hash = $password;

// Insert student
$stmt = $conn->prepare("INSERT INTO students (first_name, last_name, email, password_hash, roll_no, department, semester, user_type, profile_picture, security_question, security_answer, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
if (!$stmt) {
    send_response(false, 'Database error: ' . $conn->error);
}

$stmt->bind_param("ssssssissss",
    $first_name,
    $last_name,
    $email,
    $password_hash,
    $roll_no,
    $department,
    $semester,
    $user_type,
    $profile_picture,
    $security_question,
    $security_answer
);

if ($stmt->execute()) {
    $student_id = $stmt->insert_id;

    // Registration successful, but require admin approval before login
    send_response(true, 'Registration successful. Your account is pending admin approval.', [
        'user_id'   => $student_id,
        'redirect'  => './login.html'
    ]);
} else {
    send_response(false, 'Error creating account: ' . $stmt->error);
}

$stmt->close();
$conn->close();
?>
