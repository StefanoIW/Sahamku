<?php
require_once 'send_email.php';

$testEmail = 'youremail@gmail.com'; // Ganti dengan email Anda
$testCode = '123456';
$testName = 'Test User';

echo "Sending test email to: $testEmail<br>";
$result = sendVerificationEmail($testEmail, $testCode, $testName);

if ($result) {
    echo "✅ Email sent successfully! Check your inbox.";
} else {
    echo "❌ Email sending failed. Check error logs and SMTP settings.";
}
?>
