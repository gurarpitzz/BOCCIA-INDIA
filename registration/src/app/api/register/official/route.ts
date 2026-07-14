import { NextResponse } from 'next/server';
import { withTransaction, query } from '@/lib/db';
import { promises as fs } from 'fs';
import path from 'path';
import crypto from 'crypto';
import { resend } from '@/lib/resend';

// Helper function to check official duplicate with weighted scoring
async function checkOfficialDuplicate(name: string, dob: string, email: string, phone: string, aadhaar: string) {
  const officials = await query<any[]>(
    "SELECT id, name, dob, email, phone, aadhaar FROM officials WHERE status = 'approved' AND deleted_at IS NULL"
  );
  
  let bestMatchId: number | null = null;
  let highestScore = 0;
  
  for (const off of officials) {
    let score = 0;
    
    // Aadhaar Match (100 pts)
    if (aadhaar && off.aadhaar && aadhaar === off.aadhaar) {
      score += 100;
    }
    
    // Phone Match (40 pts)
    if (phone && off.phone && phone.replace(/\D/g, '') === off.phone.replace(/\D/g, '')) {
      score += 40;
    }
    
    // Email Match (30 pts)
    if (email && off.email && email.toLowerCase().trim() === off.email.toLowerCase().trim()) {
      score += 30;
    }
    
    // DOB Match (20 pts)
    if (dob && off.dob && dob === off.dob) {
      score += 20;
    }
    
    // Name similarity using levenshtein (10 pts)
    if (name && off.name) {
      const n1 = name.toLowerCase().trim();
      const n2 = off.name.toLowerCase().trim();
      if (n1 === n2) {
        score += 10;
        if (dob && off.dob && dob === off.dob) {
          score += 30; // boost
        }
      }
    }
    
    if (score > highestScore) {
      highestScore = score;
      bestMatchId = off.id;
    }
  }
  
  return {
    is_duplicate: highestScore >= 50,
    score: highestScore,
    official_id: bestMatchId
  };
}

export async function POST(request: Request) {
  try {
    const formData = await request.formData();
    
    // Extract and validate text fields
    const full_name = formData.get('full_name') as string;
    const role = formData.get('role') as string;
    const gender = formData.get('gender') as string;
    const dob = formData.get('dob') as string;
    const father_name = formData.get('father_name') as string; // Father's / Spouse's Name
    const phone = formData.get('phone') as string;
    const email = formData.get('email') as string;
    
    const state = formData.get('state') as string;
    const address = formData.get('address') as string;
    const pincode = formData.get('pincode') as string;
    
    const kit_tshirt = formData.get('kit_tshirt') as string;
    const kit_tracksuit = formData.get('kit_tracksuit') as string;
    const kit_shoe = formData.get('kit_shoe') as string;
    
    const aadhaar = formData.get('aadhaar') as string; // Aadhaar / Government ID

    // Files
    const photo = formData.get('photo_path') as File | null;
    const receipt = formData.get('receipt_path') as File | null; // Government ID Proof

    if (!full_name || !role || !gender || !dob || !father_name || !phone || !email || !photo || !receipt) {
      return NextResponse.json({ error: 'Required registration fields are missing.' }, { status: 400 });
    }

    const applicationUuid = crypto.randomUUID();

    // Check duplicates
    const dupResult = await checkOfficialDuplicate(full_name, dob, email, phone, aadhaar);

    // Save uploads with transaction consistency
    const result = await withTransaction(async (connection) => {
      // 1. Insert initial application record (unpopulated file paths first)
      const [insertResult]: any = await connection.execute(
        `INSERT INTO official_applications (
          application_uuid, full_name, role, gender, dob, father_name, 
          state, aadhaar, phone, email, address, pincode, 
          kit_tshirt, kit_tracksuit, kit_shoe, status, existing_official_id, 
          possible_duplicate, duplicate_score
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)`,
        [
          applicationUuid, full_name, role, gender, dob, father_name,
          state, aadhaar, phone, email, address, pincode,
          kit_tshirt, kit_tracksuit, kit_shoe, dupResult.official_id,
          dupResult.is_duplicate ? 1 : 0, dupResult.score
        ]
      );

      const applicationId = insertResult.insertId;
      const refId = `BSFI-OFF-2026-${String(applicationId).padStart(6, '0')}`;

      // 2. Write files to uploads/officials
      const baseUploadPath = path.join(process.cwd(), '../uploads/officials');
      const photoDir = path.join(baseUploadPath, 'photos');
      const docDir = path.join(baseUploadPath, 'documents');

      await fs.mkdir(photoDir, { recursive: true });
      await fs.mkdir(docDir, { recursive: true });

      const photoExt = path.extname(photo.name) || '.jpg';
      const photoName = `${crypto.randomUUID()}${photoExt}`;
      const photoPath = path.join(photoDir, photoName);
      const photoBuffer = Buffer.from(await photo.arrayBuffer());
      await fs.writeFile(photoPath, photoBuffer);

      const receiptExt = path.extname(receipt.name) || '.pdf';
      const receiptName = `${crypto.randomUUID()}${receiptExt}`;
      const receiptPath = path.join(docDir, receiptName);
      const receiptBuffer = Buffer.from(await receipt.arrayBuffer());
      await fs.writeFile(receiptPath, receiptBuffer);

      // Relative paths for storing in db to match original structure
      const dbPhotoPath = `uploads/officials/photos/${photoName}`;
      const dbReceiptPath = `uploads/officials/documents/${receiptName}`;

      // 3. Update application file paths
      await connection.execute(
        `UPDATE official_applications SET photo_path = ?, receipt_path = ? WHERE id = ?`,
        [dbPhotoPath, dbReceiptPath, applicationId]
      );

      return { refId, applicationId };
    });

    // Write audit log
    await query(
      `INSERT INTO activity_logs (action, details) VALUES (?, ?)`,
      ['Official Registration Submitted', `Application Reference: ${result.refId} for Name: ${full_name}`]
    );

    // Send confirmation email
    await resend.emails.send({
      from: 'Boccia India <noreply@bocciaindia.com>',
      to: email,
      subject: 'Official Registration Application Received - BSFI',
      html: `
        <div style="font-family: sans-serif; padding: 20px; max-width: 600px; border: 1px solid #e2e8f0; border-radius: 10px;">
          <h2 style="color: #081B4B; margin-bottom: 20px;">Boccia Sports Federation of India</h2>
          <p>Dear ${full_name},</p>
          <p>Thank you for submitting your Official/Coach Registration application. It is currently under review.</p>
          <p>Your application reference details:</p>
          <div style="background: #f1f5f9; padding: 15px; margin: 20px 0; border-radius: 6px; font-size: 16px;">
            <strong>Reference ID:</strong> ${result.refId}<br/>
            <strong>Tracking URL:</strong> <a href="${process.env.NEXT_PUBLIC_BASE_URL || 'https://bocciaindia.com'}/registration/status?id=${result.refId}" style="color: #FF9933;">Check Status</a>
          </div>
          <p>Please keep this Reference ID for all future communications.</p>
        </div>
      `,
    });

    return NextResponse.json({ success: true, refId: result.refId, uuid: applicationUuid });
  } catch (error: any) {
    console.error('Error submitting official application:', error);
    return NextResponse.json({ error: 'Server error saving registration. Please try again.' }, { status: 500 });
  }
}
