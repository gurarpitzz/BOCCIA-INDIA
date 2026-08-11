<?php
/**
 * mailer.php — Centralized email sender for BSFI
 *
 * ALL outbound emails MUST go through sendEmail().
 * Provider: Resend API (api.resend.com)
 *
 * To change provider: edit the _mailerDispatch() function only.
 * To disable all emails (e.g. during testing): set MAILER_ENABLED to false.
 */

// ─── Configuration ────────────────────────────────────────────────────────────

/** Set to false to suppress all email sending (useful for local/staging). */
define('MAILER_ENABLED', true);

/** Sender address used for all outgoing emails. */
define('MAILER_FROM', 'Boccia India <noreply@bocciaindia.com>');

/** How many times to retry after a transient failure before giving up. */
define('MAILER_MAX_RETRIES', 2);

/** Resend API request timeout (seconds). */
define('MAILER_TIMEOUT', 8);

/** Resend API connection timeout (seconds). */
define('MAILER_CONNECT_TIMEOUT', 5);

/**
 * Duplicate suppression window (seconds).
 * Prevents the same to+subject combination from being sent twice within this window.
 * Protects against double-clicks and retry loops.
 * OTP emails are exempt from this check (see sendEmail $skipDedupe parameter).
 */
define('MAILER_DEDUPE_WINDOW', 60);

// ─── Public API ───────────────────────────────────────────────────────────────

/**
 * Send an email via the configured provider (Resend).
 *
 * @param string      $to          Recipient email address.
 * @param string      $subject     Email subject line.
 * @param string      $html        HTML body content.
 * @param string|null $text        Optional plain-text fallback.
 * @param bool        $skipDedupe  Set to true for OTP/transactional emails
 *                                 where sending the same subject twice in the
 *                                 dedupe window is intentional (e.g. OTP resend).
 * @return bool true on success, false on failure.
 */
function sendEmail(string $to, string $subject, string $html, ?string $text = null, bool $skipDedupe = false, ?string $idempotencyKey = null): bool
{
    global $pdo;

    // ── Guard: disabled mode ─────────────────────────────────────────────────
    if (!MAILER_ENABLED) {
        error_log("[Mailer] DISABLED — would have sent '{$subject}' to {$to}");
        return true; // Treat as success so callers don't error
    }

    // ── Guard: invalid address ───────────────────────────────────────────────
    if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        error_log("[Mailer] Skipped — invalid or empty recipient: '{$to}'");
        return false;
    }

    // ── Duplicate suppression ────────────────────────────────────────────────
    if (!$skipDedupe && isset($pdo)) {
        try {
            $dup = $pdo->prepare(
                "SELECT COUNT(*) FROM email_logs
                 WHERE recipient = ? AND subject = ? AND status = 'sent'
                   AND sent_at >= NOW() - INTERVAL ? SECOND"
            );
            $dup->execute([$to, $subject, MAILER_DEDUPE_WINDOW]);
            if ((int)$dup->fetchColumn() > 0) {
                error_log("[Mailer] Dedupe suppressed — '{$subject}' to {$to} (within " . MAILER_DEDUPE_WINDOW . "s)");
                return true; // Treat as success; the email was already sent
            }
        } catch (\Throwable $e) {
            error_log("[Mailer] Dedupe check error (non-fatal): " . $e->getMessage());
        }
    }

    // ── Dispatch with retries ────────────────────────────────────────────────
    $attempt  = 0;
    $httpCode = 0;
    $response = '';

    while ($attempt <= MAILER_MAX_RETRIES) {
        [$httpCode, $response] = _mailerDispatch($to, $subject, $html, $text, $idempotencyKey);

        if ($httpCode >= 200 && $httpCode < 300) {
            break; // Success
        }

        $attempt++;
        if ($attempt <= MAILER_MAX_RETRIES) {
            sleep(1); // Brief pause before retry
        }
    }

    $success = ($httpCode >= 200 && $httpCode < 300);

    // ── Structured logging ───────────────────────────────────────────────────
    if (isset($pdo)) {
        try {
            $ins = $pdo->prepare(
                "INSERT INTO email_logs (recipient, subject, status, response_code, response_body, attempts, sent_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())"
            );
            $ins->execute([
                $to,
                $subject,
                $success ? 'sent' : 'failed',
                $httpCode,
                substr($response, 0, 500),
                $attempt,
            ]);
        } catch (\Throwable $e) {
            error_log("[Mailer] Failed to write email_logs: " . $e->getMessage());
        }
    }

    if (!$success) {
        error_log("[Mailer] FAILED '{$subject}' to {$to} after {$attempt} attempt(s). HTTP {$httpCode}: {$response}");
    }

    return $success;
}

// ─── Provider Layer (swap provider here only) ─────────────────────────────────

/**
 * Low-level dispatch to Resend REST API.
 * Returns [httpCode, responseBody].
 * To switch provider (SES, SMTP, SendGrid), replace this function only.
 *
 * @internal
 */
function _mailerDispatch(string $to, string $subject, string $html, ?string $text, ?string $idempotencyKey = null): array
{
    $payload = [
        'from'    => MAILER_FROM,
        'to'      => $to,
        'subject' => $subject,
        'html'    => $html,
    ];
    if ($text !== null) {
        $payload['text'] = $text;
    }

    $headers = [
        'Authorization: Bearer ' . RESEND_API_KEY,
        'Content-Type: application/json',
    ];
    if ($idempotencyKey !== null) {
        $headers[] = 'Idempotency-Key: ' . $idempotencyKey;
    }

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_TIMEOUT        => MAILER_TIMEOUT,
        CURLOPT_CONNECTTIMEOUT => MAILER_CONNECT_TIMEOUT,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_POSTFIELDS     => json_encode($payload),
    ]);

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($response === false || $curlErr) {
        error_log("[Mailer] cURL error: {$curlErr}");
        $response = $curlErr;
        $httpCode = 0;
    }

    return [$httpCode, (string)$response];
}
