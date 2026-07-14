<?php
// config/app.php - Central Application Configurations for BSFI Registration

// Check if constants are already defined to prevent warnings
if (!defined('RESEND_API_KEY')) {
    define('RESEND_API_KEY', 're_XU1ZjPKG_CKvGivqzPGdEY4C44w57hzM5');
}

if (!defined('OTP_SECRET')) {
    define('OTP_SECRET', 'boccia_secret_hmac_key_2026');
}

if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', dirname(__DIR__) . '/uploads/');
}
