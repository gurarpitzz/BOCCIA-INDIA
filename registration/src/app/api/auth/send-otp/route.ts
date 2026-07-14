import { NextResponse } from 'next/server';
import { checkRateLimit, generateOTPCode, computeOTPHash, saveOTP } from '@/lib/otp';
import { resend } from '@/lib/resend';
import { query } from '@/lib/db';

export async function POST(request: Request) {
  try {
    const { email } = await request.json();

    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      return NextResponse.json({ error: 'Valid email address is required.' }, { status: 400 });
    }

    // Get IP address for rate limiting
    const ip = request.headers.get('x-forwarded-for') || '127.0.0.1';

    // Rate Limit Check
    const rateCheck = await checkRateLimit(email, ip);
    if (!rateCheck.allowed) {
      return NextResponse.json({ error: rateCheck.message }, { status: 429 });
    }

    const otpCode = generateOTPCode();
    const otpHash = computeOTPHash(otpCode);

    // Save hashed OTP in database
    await saveOTP(email, otpHash, ip);

    // Send OTP via Resend
    await resend.emails.send({
      from: 'Boccia India <noreply@bocciaindia.com>',
      to: email,
      subject: 'Your OTP Verification Code - BSFI',
      html: `
        <div style="font-family: sans-serif; padding: 20px; max-width: 600px; border: 1px solid #e2e8f0; border-radius: 10px;">
          <h2 style="color: #081B4B; margin-bottom: 20px;">Boccia Sports Federation of India</h2>
          <p>Hello,</p>
          <p>Your 6-digit OTP verification code for registration is:</p>
          <div style="background: #f1f5f9; padding: 15px; font-size: 24px; font-weight: bold; letter-spacing: 4px; text-align: center; color: #FF9933; margin: 20px 0; border-radius: 6px;">
            ${otpCode}
          </div>
          <p style="color: #64748b; font-size: 14px;">This code is valid for 5 minutes and can only be used once. Please do not share this code with anyone.</p>
        </div>
      `,
    });

    // Log action to activity_logs
    await query(
      `INSERT INTO activity_logs (action, details) VALUES (?, ?)`,
      ['Email OTP Sent', `OTP code sent to email: ${email}`]
    );

    return NextResponse.json({ success: 'OTP sent successfully.' });
  } catch (error: any) {
    console.error('Error sending OTP:', error);
    return NextResponse.json({ error: 'Failed to send verification email. Please try again.' }, { status: 500 });
  }
}
