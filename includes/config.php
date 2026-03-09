<?php
// Database Configuration
// Update these credentials based on your Laragon setup

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'college_library');
define('DB_PORT', 3306);

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]);
    exit();
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// Function to safely escape user input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to return JSON response
function send_response($success, $message, $data = null) {
    header('Content-Type: application/json');
    $response = [
        'success' => $success,
        'message' => $message
    ];
    if ($data) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit();
}

// Session Timeout Logic (1 hr Student, 5 hr Admin)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
    $timeout = ($_SESSION['user_type'] === 'admin') ? 18000 : 3600; // 18000s = 5hrs, 3600s = 1hr
    
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        session_unset();
        session_destroy();
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Session expired due to inactivity. Please log in again.', 'session_expired' => true]);
        exit();
    }
    
    // Do not update activity timestamp if this is just the background session check
    if (basename($_SERVER['PHP_SELF']) !== 'check_session.php') {
        $_SESSION['last_activity'] = time();
    }
}
?>
