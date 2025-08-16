<?php
session_start();

// Destroy all session data
session_unset();
session_destroy();

// Start a new session for the success message
session_start();
$_SESSION['logout_message'] = 'You have been logged out successfully!';

// Redirect to login page
header("Location: homepage.php");
exit();
?>