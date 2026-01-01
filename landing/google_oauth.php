<?php
session_start();
require_once 'google_config.php';

// Generate Google OAuth URL and redirect
$authUrl = getGoogleAuthUrl();
header('Location: ' . $authUrl);
exit;
?>
