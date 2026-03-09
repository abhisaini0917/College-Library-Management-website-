<?php
session_start();

// Destroy session
session_unset();
session_destroy();

// Redirect directly to login page
header('Location: ../landing.html');
exit();
?>
