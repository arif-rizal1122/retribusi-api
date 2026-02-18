<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - SIPANDA Baubau</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; }
        .glass { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.3); }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full glass rounded-[2.5rem] shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-500">
        <!-- Header -->
        <div class="bg-gradient-to-br from-blue-600 to-indigo-700 p-8 text-center text-white">
            <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-white/30">
                @if(strpos($title, 'Bukti Bayar') !== false)
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                @else
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                @endif
            </div>
            <h1 class="text-xl font-extrabold tracking-tight uppercase">{{ $is_valid ? 'Dokumen Valid' : 'Dokumen Tidak Valid' }}</h1>
            <p class="text-blue-100 text-xs font-bold mt-1 opacity-80">{{ $title }}</p>
        </div>

        <!-- Content -->
        <div class="p-8 space-y-6">
            <div class="space-y-1">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Wajib Pajak / Retribusi</p>
                <p class="text-lg font-extrabold text-slate-800">{{ $bill->taxpayer->name }}</p>
                <p class="text-xs text-slate-500 font-bold uppercase tracking-tight">{{ $bill->taxpayer->npwpd ?? $bill->taxpayer->taxpayer_id }}</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Nomor Dokumen</p>
                    <p class="text-sm font-extrabold text-blue-600">{{ $bill->bill_number }}</p>
                </div>
                <div class="space-y-1">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Masa/Periode</p>
                    <p class="text-sm font-extrabold text-slate-700">{{ $bill->period }}</p>
                </div>
            </div>

            <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                 <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Rincian Pembayaran</p>
                 <div class="space-y-2">
                    <div class="flex justify-between text-xs">
                        <span class="text-slate-500">Pokok</span>
                        <span class="font-bold text-slate-700">Rp {{ number_format($bill->amount, 0, ',', '.') }}</span>
                    </div>
                    @if($bill->penalty_amount > 0)
                    <div class="flex justify-between text-xs">
                        <span class="text-slate-500">Sanksi Bunga</span>
                        <span class="font-bold text-slate-700">Rp {{ number_format($bill->penalty_amount, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    @if($bill->fixed_fine_amount > 0)
                    <div class="flex justify-between text-xs">
                        <span class="text-slate-500">Denda Administrasi</span>
                        <span class="font-bold text-slate-700">Rp {{ number_format($bill->fixed_fine_amount, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    @if($bill->surcharge_amount > 0)
                    <div class="flex justify-between text-xs">
                        <span class="text-slate-500">Kenaikan (Surcharge)</span>
                        <span class="font-bold text-slate-700">Rp {{ number_format($bill->surcharge_amount, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    <div class="h-px bg-slate-200 my-2"></div>
                    <div class="flex justify-between">
                        <span class="text-sm font-black text-slate-800 uppercase tracking-tight">Total</span>
                        <span class="text-lg font-black text-indigo-600">Rp {{ number_format($bill->total_amount, 0, ',', '.') }}</span>
                    </div>
                 </div>
            </div>

            @if(isset($payment))
            <div class="p-4 bg-emerald-50 rounded-2xl border border-emerald-100 text-center">
                <p class="text-[10px] font-black text-emerald-600 uppercase tracking-widest mb-1">Status Pembayaran</p>
                <p class="text-sm font-black text-emerald-700 uppercase">LUNAS PADA {{ $payment->paid_at->format('d/m/Y H:i') }}</p>
            </div>
            @else
            <div class="p-4 bg-amber-50 rounded-2xl border border-amber-100 text-center">
                <p class="text-[10px] font-black text-amber-600 uppercase tracking-widest mb-1">Status Tagihan</p>
                <p class="text-sm font-black text-amber-700 uppercase">MENUNGGU PEMBAYARAN</p>
            </div>
            @endif

            <p class="text-[9px] text-center text-slate-400 font-medium leading-relaxed italic">
                * Data ini diambil secara realtime dari sistem SIPANDA Kota Baubau. Keaslian dokumen dijamin sah secara hukum.
            </p>
        </div>

        <!-- Footer -->
        <div class="p-6 bg-slate-50 border-t border-slate-100 flex items-center justify-center gap-2">
            <span class="bg-blue-600 p-1 rounded text-white"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg></span>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Verified by V-TAX Engine</span>
        </div>
    </div>
</body>
</html>
