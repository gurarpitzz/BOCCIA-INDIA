import { NextResponse } from 'next/server';
import { verifyAndConsumeOTP } from '@/lib/otp';
import { query } from '@/lib/db';

export async function POST(request: Request) {
  try {
    const { email, otp } = await request.json();

    if (!email || !otp) {
      return NextResponse.json({ error: 'Email and OTP code are required.' }, { status: 400 });
    }

    const verified = await verifyAndConsumeOTP(email, otp);

    if (!verified) {
      return NextResponse.json({ error: 'Invalid or expired OTP code.' }, { status: 400 });
    }

    // Log action to activity_logs
    await query(
      `INSERT INTO activity_logs (action, details) VALUES (?, ?)`,
      ['OTP Verified', `Email successfully verified: ${email}`]
    );

    return NextResponse.json({ success: true });
  } catch (error) {
    console.error('Error verifying OTP:', error);
    return NextResponse.json({ error: 'An error occurred during verification.' }, { status: 500 });
  }
}
