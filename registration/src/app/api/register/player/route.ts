import { NextResponse } from 'next/server';
import { withTransaction, query } from '@/lib/db';
import { promises as fs } from 'fs';
import path from 'path';
import crypto from 'crypto';
import { resend } from '@/lib/resend';

function normalizeNameTS(name: string): string {
  if (!name) return '';
  const clean = name.replace(/[.\-,_\'\"]/g, ' ');
  return clean.replace(/\s+/g, ' ').toLowerCase().trim();
}

// Helper function to check player duplicate with weighted scoring
async function checkPlayerDuplicate(name: string, dob: string, email: string, phone: string, aadhaar: string) {
  const athletes = await query<any[]>(
    "SELECT id, regn_no, full_name, dob, email, mobile, aadhaar FROM athletes WHERE status = 'approved' AND deleted_at IS NULL"
  );
  
  let bestMatchId: number | null = null;
  let highestScore = 0;
  const normInput = normalizeNameTS(name);
  
  for (const ath of athletes) {
    let score = 0;
    
    // Aadhaar Match (100 pts)
    if (aadhaar && ath.aadhaar && aadhaar === ath.aadhaar) {
      score += 100;
    }
    
    // Phone Match (40 pts)
    if (phone && ath.mobile && phone.replace(/\D/g, '') === ath.mobile.replace(/\D/g, '')) {
      score += 40;
    }
    
    // Email Match (30 pts)
    if (email && ath.email && email.toLowerCase().trim() === ath.email.toLowerCase().trim()) {
      score += 30;
    }
    
    // DOB Match & Proximity Score (10-20 pts)
    let dobScore = 0;
    if (dob && ath.dob) {
      if (dob === ath.dob) {
        dobScore = 20;
      } else {
        const diffDays = Math.abs(new Date(dob).getTime() - new Date(ath.dob).getTime()) / (1000 * 3600 * 24);
        if (diffDays <= 15) {
          dobScore = 15;
        } else if (dob.substring(0, 7) === ath.dob.substring(0, 7)) {
          dobScore = 10;
        }
      }
    }
    score += dobScore;
    
    // Normalized & Fuzzy Name Scoring
    if (normInput && ath.full_name) {
      const normAth = normalizeNameTS(ath.full_name);
      if (normAth) {
        if (normInput === normAth) {
          score += 30;
          if (dobScore === 20) {
            score += 20; // Direct combo bonus
          }
        } else {
          // Token / word overlap check
          const w1 = normInput.split(' ').filter(w => w.length > 1);
          const w2 = normAth.split(' ').filter(w => w.length > 1);
          if (w1.length > 0 && w2.length > 0) {
            let matches = 0;
            for (const word1 of w1) {
              for (const word2 of w2) {
                if (word1 === word2 || word2.includes(word1) || word1.includes(word2)) {
                  matches++;
                  break;
                }
              }
            }
            const ratio = matches / Math.max(w1.length, w2.length);
            if (ratio >= 0.66) {
              score += 25;
            } else if (matches >= 2 || (matches >= 1 && w1.length <= 2)) {
              score += 15;
            }
          }
        }
      }
    }
    
    if (score > highestScore) {
      highestScore = score;
      bestMatchId = ath.id;
    }
  }
  
  return {
    is_duplicate: highestScore >= 50,
    score: highestScore,
    athlete_id: bestMatchId
  };
}

export async function POST(request: Request) {
  try {
    const formData = await request.formData();
    
    // Extract and validate text fields
    const full_name = formData.get('full_name') as string;
    const gender = formData.get('gender') as string;
    const dob = formData.get('dob') as string;
    const father_name = formData.get('father_name') as string;
    const mother_name = formData.get('mother_name') as string;
    const phone = formData.get('phone') as string;
    const email = formData.get('email') as string;
    
    const age_category = formData.get('age_category') as string;
    const state = formData.get('state') as string;
    const impairment_type = formData.get('impairment_type') as string;
    const classification = formData.get('classification') as string; // Boccia Category
    const wheelchair_status = formData.get('wheelchair_status') as string;
    const kit_tshirt = formData.get('kit_tshirt') as string;
    const kit_tracksuit = formData.get('kit_tracksuit') as string;
    const kit_shoe = formData.get('kit_shoe') as string;
    
    const aadhaar = formData.get('aadhaar') as string;
    const address = formData.get('address') as string;
    const pincode = formData.get('pincode') as string;

    // Files
    const photo = formData.get('photo_path') as File | null;
    const receipt = formData.get('receipt_path') as File | null; // Passport Front & Back PDF

    if (!full_name || !gender || !dob || !father_name || !mother_name || !phone || !email || !photo || !receipt) {
      return NextResponse.json({ error: 'Required registration fields are missing.' }, { status: 400 });
    }

    const applicationUuid = crypto.randomUUID();

    // Check duplicates
    const dupResult = await checkPlayerDuplicate(full_name, dob, email, phone, aadhaar);

    // Save uploads with transaction consistency
    const result = await withTransaction(async (connection) => {
      // 1. Insert initial application record (unpopulated file paths first)
      const [insertResult]: any = await connection.execute(
        `INSERT INTO athlete_applications (
          application_uuid, full_name, gender, dob, father_name, mother_name, 
          age_category, state, district, impairment_type, classification, 
          wheelchair_status, aadhaar, phone, email, address, pincode, 
          kit_tshirt, kit_tracksuit, kit_shoe, status, existing_athlete_id, 
          possible_duplicate, duplicate_score
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)`,
        [
          applicationUuid, full_name, gender, dob, father_name, mother_name,
          age_category, state, pincode, impairment_type, classification,
          wheelchair_status, aadhaar, phone, email, address, pincode,
          kit_tshirt, kit_tracksuit, kit_shoe, dupResult.athlete_id,
          dupResult.is_duplicate ? 1 : 0, dupResult.score
        ]
      );

      const applicationId = insertResult.insertId;
      const refId = `BSFI-ATH-2026-${String(applicationId).padStart(6, '0')}`;

      // 2. Write files to uploads/athletes
      const baseUploadPath = path.join(process.cwd(), '../uploads/athletes');
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
      const dbPhotoPath = `uploads/athletes/photos/${photoName}`;
      const dbReceiptPath = `uploads/athletes/documents/${receiptName}`;

      // 3. Update application file paths
      await connection.execute(
        `UPDATE athlete_applications SET photo_path = ?, receipt_path = ? WHERE id = ?`,
        [dbPhotoPath, dbReceiptPath, applicationId]
      );

      return { refId, applicationId };
    });

    // Write audit log
    await query(
      `INSERT INTO activity_logs (action, details) VALUES (?, ?)`,
      ['Player Registration Submitted', `Application Reference: ${result.refId} for Name: ${full_name}`]
    );

    // Send confirmation email
    await resend.emails.send({
      from: 'Boccia India <noreply@bocciaindia.com>',
      to: email,
      subject: 'Registration Application Received - BSFI',
      html: `
        <div style="font-family: sans-serif; padding: 20px; max-width: 600px; border: 1px solid #e2e8f0; border-radius: 10px;">
          <h2 style="color: #081B4B; margin-bottom: 20px;">Boccia Sports Federation of India</h2>
          <p>Dear ${full_name},</p>
          <p>Thank you for submitting your Player Registration application. It is currently under review.</p>
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
    console.error('Error submitting athlete application:', error);
    return NextResponse.json({ error: 'Server error saving registration. Please try again.' }, { status: 500 });
  }
}
