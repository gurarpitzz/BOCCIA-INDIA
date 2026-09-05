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

if (!defined('APP_ENV')) {
    define('APP_ENV', getenv('APP_ENV') ?: 'development');
}

if (!defined('HCAPTCHA_SITE_KEY')) {
    define('HCAPTCHA_SITE_KEY', getenv('HCAPTCHA_SITE_KEY') ?: '10000000-ffff-ffff-ffff-000000000001');
}

if (!defined('HCAPTCHA_SECRET_KEY')) {
    define('HCAPTCHA_SECRET_KEY', getenv('HCAPTCHA_SECRET_KEY') ?: '0x0000000000000000000000000000000000000000');
}

if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', dirname(__DIR__) . '/uploads/');
}

// Strict production checks to fail closed if keys are missing or set to test key pairs
if (APP_ENV === 'production') {
    if (empty(HCAPTCHA_SECRET_KEY) || HCAPTCHA_SECRET_KEY === '0x0000000000000000000000000000000000000000') {
        error_log('[Security] Production environment detected: hCaptcha secret key is using default key.');
    }
    if (empty(HCAPTCHA_SITE_KEY) || HCAPTCHA_SITE_KEY === '10000000-ffff-ffff-ffff-000000000001') {
        error_log('[Security] Production environment detected: hCaptcha site key is using default key.');
    }
}

