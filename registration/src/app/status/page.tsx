'use client';

import { useState } from 'react';

export default function StatusChecker() {
  const [refId, setRefId] = useState('');
  const [email, setEmail] = useState('');
  const [statusData, setStatusData] = useState<any>(null);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const handleCheck = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setStatusData(null);
    setLoading(true);

    try {
      const res = await fetch(`/registration/api/register/status?id=${encodeURIComponent(refId)}&email=${encodeURIComponent(email)}`);
      const data = await res.json();

      if (!res.ok) {
        setError(data.error || 'Failed to fetch registration status.');
      } else {
        setStatusData(data);
      }
    } catch (err) {
      setError('An error occurred. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <main className="min-h-screen bg-slate-900 text-white flex flex-col items-center justify-center p-4">
      <div className="max-w-md w-full bg-slate-800 border border-slate-700 rounded-3xl p-8 shadow-2xl">
        <div className="text-center mb-8">
          <h1 className="text-3xl font-extrabold text-white tracking-wide">Track Application</h1>
          <p className="text-slate-400 mt-2 text-sm">Enter your registration reference details below.</p>
        </div>

        <form onSubmit={handleCheck} className="space-y-5">
          <div>
            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Reference ID</label>
            <input
              type="text"
              value={refId}
              onChange={(e) => setRefId(e.target.value)}
              placeholder="e.g. BSFI-ATH-2026-000001"
              required
              className="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-xl focus:outline-none focus:border-orange-500 text-white placeholder-slate-600 transition"
            />
          </div>

          <div>
            <label className="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Email Address</label>
            <input
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder="Enter your registered email"
              required
              className="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-xl focus:outline-none focus:border-orange-500 text-white placeholder-slate-600 transition"
            />
          </div>

          {error && (
            <div className="bg-red-950/50 border border-red-500/50 text-red-200 p-4 rounded-xl text-sm font-medium">
              ⚠️ {error}
            </div>
          )}

          <button
            type="submit"
            disabled={loading}
            className="w-full py-4 bg-orange-500 hover:bg-orange-600 active:scale-95 disabled:opacity-50 text-white font-bold rounded-xl shadow-lg hover:shadow-orange-500/20 transition-all duration-200"
          >
            {loading ? 'Fetching...' : 'Check Status'}
          </button>
        </form>

        {statusData && (
          <div className="mt-8 pt-8 border-t border-slate-700 space-y-6">
            <h3 className="text-xl font-bold text-slate-200">Application Info</h3>
            <div className="bg-slate-900/50 border border-slate-700/50 p-6 rounded-2xl space-y-4">
              <div className="flex justify-between text-sm">
                <span className="text-slate-400">Applicant:</span>
                <span className="font-semibold">{statusData.name}</span>
              </div>
              <div className="flex justify-between text-sm items-center">
                <span className="text-slate-400">Status:</span>
                <span className={`px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider ${
                  statusData.status === 'approved' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' :
                  statusData.status === 'action required' ? 'bg-red-500/20 text-red-300 border border-red-500/30' :
                  'bg-blue-500/20 text-blue-300 border border-blue-500/30'
                }`}>
                  {statusData.status}
                </span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-slate-400">Submitted:</span>
                <span className="font-semibold">{new Date(statusData.submittedAt).toLocaleDateString()}</span>
              </div>
            </div>
          </div>
        )}
      </div>
    </main>
  );
}
