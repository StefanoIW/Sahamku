<?php
session_start();

// Check if user is logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['user_email'])) {
    // User logged in -> redirect to main dashboard
    header('Location: main/dashboard.php');
    exit;
} else {
    // User not logged in -> redirect to landing page
    header('Location: landing/index.php');
    exit;
}
?>
