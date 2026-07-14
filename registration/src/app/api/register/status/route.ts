import { NextResponse } from 'next/server';
import { query } from '@/lib/db';

export async function GET(request: Request) {
  try {
    const { searchParams } = new URL(request.url);
    const refId = searchParams.get('id')?.trim();
    const email = searchParams.get('email')?.trim();

    if (!refId || !email) {
      return NextResponse.json({ error: 'Reference ID and Email address are required.' }, { status: 400 });
    }

    const matches = refId.match(/^BSFI-(ATH|OFF)-2026-(\d+)$/i);
    if (!matches) {
      return NextResponse.json({ error: 'Invalid Reference ID format.' }, { status: 400 });
    }

    const type = matches[1].toUpperCase();
    const rowId = parseInt(matches[2], 10);

    let application: any = null;

    if (type === 'ATH') {
      const rows = await query<any[]>(
        `SELECT full_name, email, status, created_at FROM athlete_applications WHERE id = ? AND email = ?`,
        [rowId, email]
      );
      application = rows[0] || null;
    } else if (type === 'OFF') {
      const rows = await query<any[]>(
        `SELECT full_name, email, status, created_at FROM official_applications WHERE id = ? AND email = ?`,
        [rowId, email]
      );
      application = rows[0] || null;
    }

    if (!application) {
      return NextResponse.json({ error: 'No matching application found.' }, { status: 404 });
    }

    // Mask status values: "rejected" -> "action required"
    let publicStatus = application.status;
    if (publicStatus === 'rejected') {
      publicStatus = 'action required';
    } else if (publicStatus === 'pending') {
      publicStatus = 'submitted';
    }

    // Audit view log
    await query(
      `INSERT INTO activity_logs (action, details) VALUES (?, ?)`,
      ['Status Viewed', `Tracking status viewed for Reference ID: ${refId}`]
    );

    return NextResponse.json({
      name: application.full_name,
      status: publicStatus,
      submittedAt: application.created_at
    });
  } catch (error) {
    console.error('Error fetching application status:', error);
    return NextResponse.json({ error: 'Server error retrieving status.' }, { status: 500 });
  }
}
