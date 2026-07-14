<?php
// config/app.php - Central Application Configurations for BSFI Registration

// 1. Load local configuration overrides (ignored by Git)
if (file_exists(__DIR__ . '/local.php')) {
    include_once __DIR__ . '/local.php';
}

// 2. Define configurations with env lookup or local definitions fallbacks
if (!defined('RESEND_API_KEY')) {
    define('RESEND_API_KEY', getenv('RESEND_API_KEY') ?: '');
}

if (!defined('OTP_SECRET')) {
    define('OTP_SECRET', getenv('OTP_SECRET') ?: 'boccia_secret_hmac_key_2026');
}

if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', dirname(__DIR__) . '/uploads/');
}
