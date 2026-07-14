'use client';

import { useState, useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';
import { motion, AnimatePresence } from 'framer-motion';
import { ArrowLeft, ArrowRight, CheckCircle, UploadCloud } from 'lucide-react';

const DRAFT_KEY = 'bsfi_official_registration_draft';

const statesList = [
  "Andhra Pradesh", "Arunachal Pradesh", "Assam", "Bihar", "Chhattisgarh", "Goa", "Gujarat",
  "Haryana", "Himachal Pradesh", "Jharkhand", "Karnataka", "Kerala", "Madhya Pradesh", "Maharashtra",
  "Manipur", "Meghalaya", "Mizoram", "Nagaland", "Odisha", "Punjab", "Rajasthan", "Sikkim", "Tamil Nadu",
  "Telangana", "Tripura", "Uttar Pradesh", "Uttarakhand", "West Bengal", "Andaman and Nicobar Islands",
  "Chandigarh", "Dadra and Nagar Haveli and Daman and Diu", "Delhi", "Jammu and Kashmir", "Ladakh",
  "Lakshadweep", "Puducherry"
];

// Zod schemas per step
const step1Schema = z.object({
  full_name: z.string().min(2, 'Name must be at least 2 characters'),
  role: z.string().refine((val) => ["Coach", "Sport Assistant", "Classifier", "Technical Official", "Referee", "Volunteer"].includes(val), {
    message: 'Please select a registration role'
  }),
  gender: z.string().refine((val) => ['Male', 'Female', 'Other'].includes(val), {
    message: 'Please select gender'
  }),
  dob: z.string().refine((val) => !isNaN(Date.parse(val)), { message: 'Valid Date of Birth is required' }),
  father_name: z.string().min(2, 'Father\'s or Spouse\'s Name must be at least 2 characters'),
  phone: z.string().regex(/^\d{10}$/, 'Phone number must be exactly 10 digits'),
  email: z.string().email('Invalid email address'),
});

const step2Schema = z.object({
  state: z.string().min(1, 'Please select state'),
  address: z.string().min(10, 'Address must be at least 10 characters'),
  pincode: z.string().regex(/^\d{6}$/, 'Pincode must be exactly 6 digits'),
  kit_tshirt: z.string().min(1, 'T-Shirt size is required'),
  kit_tracksuit: z.string().min(1, 'Track Suit size is required'),
  kit_shoe: z.string().min(1, 'Shoe size is required'),
});

const step3Schema = z.object({
  aadhaar: z.string().min(4, 'ID Number must be entered'),
});

export default function OfficialRegistration() {
  const [step, setStep] = useState(0); // 0: Verification, 1: Step 1, 2: Step 2, 3: Step 3, 4: Success
  const [otpSent, setOtpSent] = useState(false);
  const [otpCode, setOtpCode] = useState('');
  const [isEmailVerified, setIsEmailVerified] = useState(false);
  const [cooldown, setCooldown] = useState(0);
  
  const [photoFile, setPhotoFile] = useState<File | null>(null);
  const [docFile, setDocFile] = useState<File | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [regResult, setRegResult] = useState<any>(null);
  const [errorMsg, setErrorMsg] = useState('');
  const [showDraftModal, setShowDraftModal] = useState(false);

  // Active validation schema based on step index
  const activeSchema = step === 1 ? step1Schema : step === 2 ? step2Schema : step3Schema;

  const { register, handleSubmit, formState: { errors }, watch, trigger, setValue, reset } = useForm({
    resolver: zodResolver(activeSchema),
    mode: 'all',
  });

  const emailVal = watch('email');
  const watchedFields = watch();

  // Cooldown countdown timer
  useEffect(() => {
    if (cooldown > 0) {
      const timer = setTimeout(() => setCooldown(cooldown - 1), 1000);
      return () => clearTimeout(timer);
    }
  }, [cooldown]);

  // Load draft check
  useEffect(() => {
    const draft = localStorage.getItem(DRAFT_KEY);
    if (draft) {
      setShowDraftModal(true);
    }
  }, []);

  // Save draft state
  useEffect(() => {
    if (step >= 1 && step <= 3) {
      localStorage.setItem(DRAFT_KEY, JSON.stringify({
        step,
        formData: watchedFields,
        timestamp: Date.now()
      }));
    }
  }, [watchedFields, step]);

  const loadDraft = () => {
    const draftJson = localStorage.getItem(DRAFT_KEY);
    if (draftJson) {
      const { step: draftStep, formData } = JSON.parse(draftJson);
      reset(formData);
      setIsEmailVerified(true);
      setStep(draftStep);
    }
    setShowDraftModal(false);
  };

  const discardDraft = () => {
    localStorage.removeItem(DRAFT_KEY);
    setShowDraftModal(false);
  };

  const handleSendOTP = async () => {
    setErrorMsg('');
    try {
      const res = await fetch('/registration/api/auth/send-otp', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: emailVal })
      });
      const data = await res.json();
      if (!res.ok) {
        setErrorMsg(data.error || 'Failed to send OTP.');
      } else {
        setOtpSent(true);
        setCooldown(60);
      }
    } catch {
      setErrorMsg('Failed to dispatch OTP code. Please retry.');
    }
  };

  const handleVerifyOTP = async () => {
    setErrorMsg('');
    try {
      const res = await fetch('/registration/api/auth/verify-otp', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: emailVal, otp: otpCode })
      });
      if (!res.ok) {
        const data = await res.json();
        setErrorMsg(data.error || 'Invalid OTP code.');
      } else {
        setIsEmailVerified(true);
        setStep(1);
      }
    } catch {
      setErrorMsg('OTP verification failed.');
    }
  };

  const nextStep = async () => {
    const isValid = await trigger();
    if (isValid) {
      setStep((prev) => prev + 1);
    }
  };

  const prevStep = () => {
    setStep((prev) => prev - 1);
  };

  const onSubmitForm = async () => {
    if (!photoFile || !docFile) {
      setErrorMsg('Please upload both required photo and government ID proof.');
      return;
    }

    setErrorMsg('');
    setSubmitting(true);

    const fd = new FormData();
    Object.entries(watchedFields).forEach(([key, val]) => {
      fd.append(key, val as string);
    });
    fd.append('photo_path', photoFile);
    fd.append('receipt_path', docFile);

    try {
      const res = await fetch('/registration/api/register/official', {
        method: 'POST',
        body: fd
      });
      const data = await res.json();
      if (!res.ok) {
        setErrorMsg(data.error || 'Registration failed.');
      } else {
        setRegResult(data);
        localStorage.removeItem(DRAFT_KEY);
        setStep(4);
      }
    } catch {
      setErrorMsg('An error occurred during submission.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <main className="min-h-screen bg-slate-950 text-white flex flex-col justify-center items-center p-4">
      {/* Draft Restore Modal */}
      {showDraftModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
          <div className="bg-slate-900 border border-slate-800 rounded-3xl p-8 max-w-md w-full shadow-2xl text-center space-y-6">
            <h3 className="text-2xl font-bold">Unfinished Application</h3>
            <p className="text-slate-400 text-sm">We found a saved draft. Would you like to resume where you left off?</p>
            <div className="grid grid-cols-2 gap-4">
              <button onClick={loadDraft} className="py-3 bg-orange-500 rounded-xl font-bold transition hover:bg-orange-600">Resume</button>
              <button onClick={discardDraft} className="py-3 bg-slate-800 border border-slate-700 rounded-xl font-bold transition hover:bg-slate-700">Start Over</button>
            </div>
          </div>
        </div>
      )}

      <div className="max-w-2xl w-full bg-slate-900 border border-slate-800 rounded-3xl p-8 md:p-12 shadow-2xl space-y-8">
        <header className="text-center space-y-2">
          <h1 className="text-3xl md:text-4xl font-extrabold text-white">Official Registration</h1>
          <p className="text-slate-400 text-sm">Boccia Sports Federation of India Portal</p>
        </header>

        {/* Progress Indicator */}
        {step >= 1 && step <= 3 && (
          <div className="space-y-3">
            <div className="flex justify-between text-xs font-semibold text-slate-400 uppercase tracking-wider">
              <span>Step {step} of 3</span>
              <span>{step === 1 ? 'Personal Details' : step === 2 ? 'Official Address & Size' : 'Identity Verification'}</span>
            </div>
            <div className="h-2 bg-slate-800 rounded-full overflow-hidden">
              <motion.div
                className="h-full bg-orange-500 rounded-full"
                animate={{ width: `${(step / 3) * 100}%` }}
                transition={{ duration: 0.3 }}
              />
            </div>
          </div>
        )}

        <AnimatePresence mode="wait">
          {step === 0 && (
            <motion.div
              key="otp-step"
              initial={{ opacity: 0, x: -20 }}
              animate={{ opacity: 1, x: 0 }}
              exit={{ opacity: 0, x: 20 }}
              className="space-y-6"
            >
              <h3 className="text-xl font-bold">Email Verification</h3>
              <p className="text-slate-400 text-sm">Please verify your email address to start the registration wizard.</p>
              
              <div className="space-y-4">
                <div>
                  <label className="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Email Address</label>
                  <div className="flex gap-3">
                    <input
                      type="email"
                      placeholder="e.g. official@gmail.com"
                      {...register('email')}
                      className="flex-1 px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl focus:outline-none focus:border-orange-500"
                    />
                    <button
                      type="button"
                      disabled={cooldown > 0}
                      onClick={handleSendOTP}
                      className="px-6 py-3 bg-orange-500 hover:bg-orange-600 disabled:opacity-50 text-white font-bold rounded-xl transition"
                    >
                      {cooldown > 0 ? `Resend (${cooldown}s)` : 'Send OTP'}
                    </button>
                  </div>
                  {errors.email && <p className="text-red-500 text-xs mt-1">{errors.email.message}</p>}
                </div>

                {otpSent && (
                  <div>
                    <label className="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Enter 6-Digit OTP</label>
                    <div className="flex gap-3">
                      <input
                        type="text"
                        maxLength={6}
                        placeholder="000000"
                        value={otpCode}
                        onChange={(e) => setOtpCode(e.target.value)}
                        className="flex-1 px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl text-center font-bold tracking-widest text-2xl"
                      />
                      <button
                        type="button"
                        onClick={handleVerifyOTP}
                        className="px-8 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl transition"
                      >
                        Verify Code
                      </button>
                    </div>
                  </div>
                )}
              </div>
            </motion.div>
          )}

          {step === 1 && (
            <motion.div
              key="step-1"
              initial={{ opacity: 0, x: -20 }}
              animate={{ opacity: 1, x: 0 }}
              exit={{ opacity: 0, x: 20 }}
              className="space-y-6"
            >
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label className="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Full Name</label>
                  <input type="text" {...register('full_name')} className="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl" />
                  {errors.full_name && <p className="text-red-500 text-xs mt-1">{errors.full_name.message}</p>}
                </div>
                <div>
                  <label className="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Registration Role</label>
                  <select {...register('role')} className="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl">
                    <option value="">Select Role</option>
                    <option value="Coach">Coach</option>
                    <option value="Sport Assistant">Sport Assistant</option>
                    <option value="Classifier">Classifier</option>
                    <option value="Technical Official">Technical Official</option>
                    <option value="Referee">Referee</option>
                    <option value="Volunteer">Volunteer</option>
                  </select>
                  {errors.role && <p className="text-red-500 text-xs mt-1">{errors.role.message}</p>}
                </div>
                <div>
                  <label className="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Gender</label>
                  <select {...register('gender')} className="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl">
                    <option value="">Select Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                  </select>
                  {errors.gender && <p className="text-red-500 text-xs mt-1">{errors.gender.message}</p>}
                </div>
                <div>
                  <label className="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Date of Birth</label>
                  <input type="date" {...register('dob')} className="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl text-slate-300" />
                  {errors.dob && <p className="text-red-500 text-xs mt-1">{errors.dob.message}</p>}
                </div>
                <div>
                  <label className="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Father's / Spouse's Name</label>
                  <input type="text" {...register('father_name')} className="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl" />
                  {errors.father_name && <p className="text-red-500 text-xs mt-1">{errors.father_name.message}</p>}
                </div>
                <div>
                  <label className="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Phone Number</label>
                  <input type="tel" {...register('phone')} className="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl" />
                  {errors.phone && <p className="text-red-500 text-xs mt-1">{errors.phone.message}</p>}
                </div>
              </div>

              <div className="flex justify-end pt-4">
                <button
                  onClick={nextStep}
                  className="px-8 py-3 bg-orange-500 hover:bg-orange-600 font-bold rounded-xl flex items-center gap-2 transition"
                >
                  Continue <ArrowRight size={16} />
                </button>
              </div>
            </motion.div>
          )}

          {step === 2 && (
            <motion.div
              key="step-2"
              initial={{ opacity: 0, x: -20 }}
              animate={{ opacity: 1, x: 0 }}
              exit={{ opacity: 0, x: 20 }}
              className="space-y-6"
            >
              <div className="space-y-6">
                <div>
                  <label className="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">State / UT</label>
                  <select {...register('state')} className="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl">
                    <option value="">Select State</option>
                    {statesList.map(s => <option key={s} value={s}>{s}</option>)}
                  </select>
                  {errors.state && <p className="text-red-500 text-xs mt-1">{errors.state.message}</p>}
                </div>
                <div>
                  <label className="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Permanent Address</label>
                  <textarea rows={3} {...register('address')} className="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl" />
                  {errors.address && <p className="text-red-500 text-xs mt-1">{errors.address.message}</p>}
                </div>
                <div>
                  <label className="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Pin Code</label>
                  <input type="text" maxLength={6} {...register('pincode')} className="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl" />
                  {errors.pincode && <p className="text-red-500 text-xs mt-1">{errors.pincode.message}</p>}
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                  <div>
                    <label className="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">T-Shirt Size</label>
                    <select {...register('kit_tshirt')} className="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl">
                      <option value="">Select Size</option>
                      <option value="XS">XS</option>
                      <option value="S">S</option>
                      <option value="M">M</option>
                      <option value="L">L</option>
                      <option value="XL">XL</option>
                      <option value="XXL">XXL</option>
                    </select>
                    {errors.kit_tshirt && <p className="text-red-500 text-xs mt-1">{errors.kit_tshirt.message}</p>}
                  </div>
                  <div>
                    <label className="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Track Suit Size</label>
                    <select {...register('kit_tracksuit')} className="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl">
                      <option value="">Select Size</option>
                      <option value="XS">XS</option>
                      <option value="S">S</option>
                      <option value="M">M</option>
                      <option value="L">L</option>
                      <option value="XL">XL</option>
                      <option value="XXL">XXL</option>
                    </select>
                    {errors.kit_tracksuit && <p className="text-red-500 text-xs mt-1">{errors.kit_tracksuit.message}</p>}
                  </div>
                  <div>
                    <label className="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Shoe Size (UK)</label>
                    <select {...register('kit_shoe')} className="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl">
                      <option value="">Select Size</option>
                      <option value="5">5</option>
                      <option value="6">6</option>
                      <option value="7">7</option>
                      <option value="8">8</option>
                      <option value="9">9</option>
                      <option value="10">10</option>
                      <option value="11">11</option>
                      <option value="12">12</option>
                    </select>
                    {errors.kit_shoe && <p className="text-red-500 text-xs mt-1">{errors.kit_shoe.message}</p>}
                  </div>
                </div>
              </div>

              <div className="flex justify-between pt-4">
                <button
                  onClick={prevStep}
                  className="px-6 py-3 bg-slate-800 hover:bg-slate-700 font-bold rounded-xl flex items-center gap-2 transition"
                >
                  <ArrowLeft size={16} /> Back
                </button>
                <button
                  onClick={nextStep}
                  className="px-8 py-3 bg-orange-500 hover:bg-orange-600 font-bold rounded-xl flex items-center gap-2 transition"
                >
                  Continue <ArrowRight size={16} />
                </button>
              </div>
            </motion.div>
          )}

          {step === 3 && (
            <motion.div
              key="step-3"
              initial={{ opacity: 0, x: -20 }}
              animate={{ opacity: 1, x: 0 }}
              exit={{ opacity: 0, x: 20 }}
              className="space-y-6"
            >
              <div className="space-y-6">
                <div>
                  <label className="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Aadhaar / Government ID Number</label>
                  <input type="text" {...register('aadhaar')} className="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl" />
                  {errors.aadhaar && <p className="text-red-500 text-xs mt-1">{errors.aadhaar.message}</p>}
                </div>

                {/* File Upload fields */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label className="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Passport Size Photo (JPG/PNG)</label>
                    <div className="border-2 border-dashed border-slate-800 hover:border-orange-500 rounded-2xl p-6 text-center cursor-pointer transition relative">
                      <input
                        type="file"
                        accept="image/jpeg,image/png"
                        onChange={(e) => setPhotoFile(e.target.files?.[0] || null)}
                        className="absolute inset-0 opacity-0 cursor-pointer"
                      />
                      <UploadCloud className="mx-auto mb-2 text-slate-500" size={32} />
                      <span className="text-sm font-semibold">{photoFile ? photoFile.name : 'Choose Photo File'}</span>
                    </div>
                  </div>
                  <div>
                    <label className="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Government ID Proof (PDF/JPG/PNG)</label>
                    <div className="border-2 border-dashed border-slate-800 hover:border-orange-500 rounded-2xl p-6 text-center cursor-pointer transition relative">
                      <input
                        type="file"
                        accept="application/pdf,image/jpeg,image/png"
                        onChange={(e) => setDocFile(e.target.files?.[0] || null)}
                        className="absolute inset-0 opacity-0 cursor-pointer"
                      />
                      <UploadCloud className="mx-auto mb-2 text-slate-500" size={32} />
                      <span className="text-sm font-semibold">{docFile ? docFile.name : 'Choose ID Proof Document'}</span>
                    </div>
                  </div>
                </div>
              </div>

              <div className="flex justify-between pt-4">
                <button
                  onClick={prevStep}
                  className="px-6 py-3 bg-slate-800 hover:bg-slate-700 font-bold rounded-xl flex items-center gap-2 transition"
                >
                  <ArrowLeft size={16} /> Back
                </button>
                <button
                  onClick={onSubmitForm}
                  disabled={submitting}
                  className="px-8 py-3 bg-orange-500 hover:bg-orange-600 disabled:opacity-50 font-bold rounded-xl flex items-center gap-2 transition"
                >
                  {submitting ? 'Submitting...' : 'Submit Registration'}
                </button>
              </div>
            </motion.div>
          )}

          {step === 4 && regResult && (
            <motion.div
              key="success-step"
              initial={{ opacity: 0, scale: 0.95 }}
              animate={{ opacity: 1, scale: 1 }}
              className="text-center space-y-6 py-8"
            >
              <CheckCircle className="mx-auto text-emerald-500" size={64} />
              <h2 className="text-3xl font-extrabold text-white">Registration Submitted</h2>
              <p className="text-slate-400 text-sm max-w-md mx-auto">Your Official / Coach application has been received and is currently under review by BSFI coordinators.</p>
              
              <div className="bg-slate-900 border border-slate-800 p-6 rounded-2xl max-w-sm mx-auto space-y-3">
                <span className="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Your Reference ID</span>
                <span className="block text-2xl font-black text-orange-500 tracking-wider">{regResult.refId}</span>
              </div>

              <div className="pt-6">
                <button
                  onClick={() => {
                    setStep(0);
                    setOtpSent(false);
                    setOtpCode('');
                    setIsEmailVerified(false);
                    setPhotoFile(null);
                    setDocFile(null);
                    reset();
                  }}
                  className="px-6 py-3 bg-slate-800 border border-slate-700 hover:bg-slate-700 font-bold rounded-xl transition"
                >
                  Register Another Official
                </button>
              </div>
            </motion.div>
          )}
        </AnimatePresence>

        {errorMsg && (
          <div className="bg-red-950/50 border border-red-500/50 text-red-200 p-4 rounded-xl text-sm font-semibold">
            ⚠️ {errorMsg}
          </div>
        )}
      </div>
    </main>
  );
}
