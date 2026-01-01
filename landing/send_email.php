<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// SMTP Configuration - Gunakan Gmail SMTP
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'gunturtjoa18@gmail.com'); // Ganti dengan email Anda
define('SMTP_PASSWORD', 'rifc jljf orkb yyvf'); // App Password dari Google
define('SMTP_FROM_EMAIL', 'noreply@sahamqu.com');
define('SMTP_FROM_NAME', 'SahamQu - Smart Trading');

/**
 * Send verification email using PHPMailer
 */
function sendVerificationEmail($email, $code, $name = 'User') {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($email, $name);
        $mail->addReplyTo('support@sahamqu.com', 'SahamQu Support');
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "🔐 Kode Verifikasi SahamQu - $code";
        
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body { 
                    font-family: 'Segoe UI', Arial, sans-serif; 
                    background: #0a0e1a; 
                    color: #f1f5f9; 
                    margin: 0; 
                    padding: 0; 
                }
                .container { 
                    max-width: 600px; 
                    margin: 20px auto; 
                    background: #1e293b;
                    border-radius: 16px;
                    overflow: hidden;
                    box-shadow: 0 10px 40px rgba(0,0,0,0.5);
                }
                .header { 
                    background: linear-gradient(135deg, #1e40af, #3730a3); 
                    padding: 40px 30px; 
                    text-align: center;
                }
                .header h1 { 
                    color: #fbbf24; 
                    margin: 0; 
                    font-size: 32px; 
                    font-weight: 900;
                }
                .header p { 
                    color: #cbd5e1; 
                    margin: 10px 0 0 0; 
                    font-size: 14px;
                }
                .content { 
                    padding: 40px 30px; 
                }
                .greeting {
                    font-size: 20px;
                    color: #fbbf24;
                    margin-bottom: 20px;
                    font-weight: 600;
                }
                .message {
                    color: #cbd5e1;
                    line-height: 1.8;
                    margin-bottom: 30px;
                }
                .code-box { 
                    background: linear-gradient(135deg, #1e40af, #3730a3);
                    padding: 30px; 
                    text-align: center; 
                    border-radius: 12px;
                    margin: 30px 0;
                    border: 3px solid #fbbf24;
                }
                .code-label {
                    color: #cbd5e1;
                    font-size: 14px;
                    margin-bottom: 15px;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                }
                .code { 
                    font-size: 56px; 
                    font-weight: 900; 
                    letter-spacing: 12px;
                    color: #fbbf24;
                    font-family: 'Courier New', monospace;
                }
                .warning {
                    background: rgba(239, 68, 68, 0.1);
                    border-left: 4px solid #ef4444;
                    padding: 15px 20px;
                    border-radius: 8px;
                    margin: 25px 0;
                }
                .warning-text {
                    color: #fca5a5;
                    margin: 0;
                    font-size: 14px;
                }
                .info-box {
                    background: rgba(30, 64, 175, 0.2);
                    padding: 20px;
                    border-left: 4px solid #fbbf24;
                    border-radius: 8px;
                    margin-top: 25px;
                }
                .info-text {
                    color: #cbd5e1;
                    margin: 0;
                    font-size: 14px;
                    line-height: 1.6;
                }
                .footer { 
                    background: #0f172a;
                    text-align: center; 
                    padding: 30px 20px; 
                    color: #94a3b8; 
                    font-size: 13px;
                }
                .footer-links {
                    margin: 15px 0;
                }
                .footer-link {
                    color: #fbbf24;
                    text-decoration: none;
                    margin: 0 10px;
                }
                .social-links {
                    margin-top: 20px;
                }
                .social-link {
                    display: inline-block;
                    width: 36px;
                    height: 36px;
                    background: rgba(251, 191, 36, 0.1);
                    border-radius: 50%;
                    margin: 0 5px;
                    line-height: 36px;
                    color: #fbbf24;
                    text-decoration: none;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🚀 SahamQu</h1>
                    <p>Smart Trading Platform</p>
                </div>
                <div class='content'>
                    <div class='greeting'>Halo, $name! 👋</div>
                    
                    <div class='message'>
                        Terima kasih telah mendaftar di <strong style='color: #fbbf24;'>SahamQu</strong>. 
                        Untuk melanjutkan dan mengaktifkan akun Anda, silakan masukkan kode verifikasi berikut:
                    </div>
                    
                    <div class='code-box'>
                        <div class='code-label'>Kode Verifikasi Anda</div>
                        <div class='code'>$code</div>
                    </div>
                    
                    <div class='warning'>
                        <p class='warning-text'>
                            ⏱️ <strong>PENTING:</strong> Kode ini akan kadaluarsa dalam <strong>10 menit</strong>.
                        </p>
                    </div>
                    
                    <div class='info-box'>
                        <p class='info-text'>
                            💡 <strong>Keamanan Tips:</strong><br>
                            • Jangan bagikan kode ini kepada siapapun<br>
                            • Tim SahamQu tidak akan pernah meminta kode verifikasi<br>
                            • Jika Anda tidak merasa mendaftar, abaikan email ini
                        </p>
                    </div>
                </div>
                <div class='footer'>
                    <p><strong>© 2025 SahamQu</strong> - Smart Trading Platform</p>
                    <p>Made with ❤️ in Indonesia</p>
                    <div class='footer-links'>
                        <a href='#' class='footer-link'>Help Center</a> •
                        <a href='#' class='footer-link'>Privacy Policy</a> •
                        <a href='#' class='footer-link'>Terms of Service</a>
                    </div>
                    <div class='social-links'>
                        <a href='#' class='social-link'>📘</a>
                        <a href='#' class='social-link'>🐦</a>
                        <a href='#' class='social-link'>📷</a>
                        <a href='#' class='social-link'>💼</a>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Plain text alternative
        $mail->AltBody = "
        SahamQu - Kode Verifikasi
        
        Halo $name,
        
        Kode verifikasi Anda: $code
        
        Kode ini akan kadaluarsa dalam 10 menit.
        Jika Anda tidak merasa mendaftar, abaikan email ini.
        
        © 2025 SahamQu
        ";
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Email sending failed: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Generate 6-digit verification code
 */
function generateVerificationCode() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}
?>
