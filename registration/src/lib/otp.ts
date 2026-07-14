import crypto from 'crypto';
import { query } from './db';

const OTP_EXPIRY_MINUTES = 5;
const RATE_LIMIT_EMAIL_MAX = 5;
const RATE_LIMIT_IP_MAX = 20;

export function generateOTPCode(): string {
  // Generate cryptographically secure 6-digit numeric OTP
  return Math.floor(100000 + crypto.randomInt(900000)).toString();
}

export function computeOTPHash(otp: string): string {
  const secret = process.env.OTP_SECRET || 'fallback_secret';
  return crypto.createHmac('sha256', secret).update(otp).digest('hex');
}

export async function checkRateLimit(email: string, ip: string): Promise<{ allowed: boolean; message?: string }> {
  // 1. Clean up expired OTPs older than 24 hours
  await query(
    `DELETE FROM email_otps WHERE expires_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)`
  );

  // 2. Count requests for this email in the last hour
  const emailRows = await query<{ count: number }[]>(
    `SELECT COUNT(*) as count FROM email_otps WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)`,
    [email]
  );
  if (emailRows[0] && emailRows[0].count >= RATE_LIMIT_EMAIL_MAX) {
    return { allowed: false, message: 'Too many OTP requests for this email. Please try again in an hour.' };
  }

  // 3. Count requests for this IP in the last hour
  const ipRows = await query<{ count: number }[]>(
    `SELECT COUNT(*) as count FROM email_otps WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)`,
    [ip]
  );
  if (ipRows[0] && ipRows[0].count >= RATE_LIMIT_IP_MAX) {
    return { allowed: false, message: 'Too many OTP requests from this connection. Please try again in an hour.' };
  }

  return { allowed: true };
}

export async function saveOTP(email: string, otpHash: string, ip: string): Promise<void> {
  // Deactivate/delete any active unexpired OTPs for this email to ensure only one valid OTP exists
  await query(`DELETE FROM email_otps WHERE email = ?`, [email]);

  const expiresAt = new Date(Date.now() + OTP_EXPIRY_MINUTES * 60 * 1000);
  
  await query(
    `INSERT INTO email_otps (email, otp_hash, expires_at, ip_address) VALUES (?, ?, ?, ?)`,
    [email, otpHash, expiresAt, ip]
  );
}

export async function verifyAndConsumeOTP(email: string, otp: string): Promise<boolean> {
  const hash = computeOTPHash(otp);

  // Fetch active OTP for the email
  const rows = await query<any[]>(
    `SELECT * FROM email_otps WHERE email = ? AND verified = 0 AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1`,
    [email]
  );

  if (rows.length === 0) {
    return false;
  }

  const record = rows[0];

  // Max 5 attempts
  if (record.attempts >= 5) {
    await query(`DELETE FROM email_otps WHERE email = ?`, [email]);
    return false;
  }

  if (record.otp_hash !== hash) {
    await query(`UPDATE email_otps SET attempts = attempts + 1 WHERE id = ?`, [record.id]);
    return false;
  }

  // Successful verification -> delete OTP records to prevent reuse
  await query(`DELETE FROM email_otps WHERE email = ?`, [email]);
  return true;
}
