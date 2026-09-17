<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - Mpad Baubau</title>
    @vite(['resources/css/app.css'])
    <style>
        .glass { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.3); }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 bg-slate-50">
    <div class="max-w-md w-full glass rounded-[2.5rem] shadow-2xl overflow-hidden">
        <div class="p-8 text-center text-white" style="background-image: linear-gradient(to right bottom, #2563eb, #4338ca);">
            <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-white/30">
                @if($is_valid)
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                @else
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                @endif
            </div>
            <h1 class="text-xl font-extrabold tracking-tight uppercase">{{ $is_valid ? 'Dokumen Valid' : 'Dokumen Tidak Ditemukan' }}</h1>
            <p class="text-blue-100 text-xs font-bold mt-1 opacity-80">{{ $doc_label }}</p>
        </div>

        <div class="p-8 space-y-6">
            <div class="space-y-1">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Nomor Dokumen</p>
                <p class="text-base font-extrabold text-blue-600 break-all">{{ $number }}</p>
            </div>

            <div class="space-y-1">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Jenis Dokumen</p>
                <p class="text-sm font-bold text-slate-700">{{ $doc_label }}</p>
            </div>

            @if($bill)
            <div class="space-y-1">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Wajib Pajak / Retribusi</p>
                <p class="text-lg font-extrabold text-slate-800">{{ $bill->taxpayer->name ?? '-' }}</p>
                <p class="text-xs text-slate-500 font-bold uppercase tracking-tight">{{ $bill->taxpayer->npwpd ?? ($bill->taxpayer->taxpayer_id ?? '-') }}</p>
            </div>

            <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Rincian</p>
                <div class="space-y-2">
                    <div class="flex justify-between text-xs">
                        <span class="text-slate-500">Pokok</span>
                        <span class="font-bold text-slate-700">Rp {{ number_format($bill->amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="h-px bg-slate-200 my-2"></div>
                    <div class="flex justify-between">
                        <span class="text-sm font-black text-slate-800 uppercase tracking-tight">Total</span>
                        <span class="text-lg font-black text-indigo-600">Rp {{ number_format($bill->total_amount ?? $bill->amount, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
            @endif

            @if(isset($payment))
            <div class="p-4 bg-emerald-50 rounded-2xl border border-emerald-100 text-center">
                <p class="text-[10px] font-black text-emerald-600 uppercase tracking-widest mb-1">Status Pembayaran</p>
                <p class="text-sm font-black text-emerald-700 uppercase">LUNAS PADA {{ $payment->paid_at->format('d/m/Y H:i') }}</p>
            </div>
            @elseif($bill)
            <div class="p-4 bg-amber-50 rounded-2xl border border-amber-100 text-center">
                <p class="text-[10px] font-black text-amber-600 uppercase tracking-widest mb-1">Status Tagihan</p>
                <p class="text-sm font-black text-amber-700 uppercase">MENUNGGU PEMBAYARAN</p>
            </div>
            @endif

            <p class="text-[9px] text-center text-slate-400 font-medium leading-relaxed italic">
                * Data ini diambil secara realtime dari sistem Mpad Kota Baubau. Keaslian dokumen dijamin sah secara hukum.
            </p>
        </div>

        <div class="p-6 bg-slate-50 border-t border-slate-100 flex items-center justify-center gap-2">
            <span class="bg-blue-600 p-1 rounded text-white"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg></span>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Verified by V-TAX Engine</span>
        </div>
    </div>
</body>
</html>
