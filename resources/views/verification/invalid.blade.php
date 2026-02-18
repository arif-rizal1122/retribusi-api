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
        <div class="bg-gradient-to-br from-rose-600 to-pink-700 p-8 text-center text-white">
            <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-white/30">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <h1 class="text-xl font-extrabold tracking-tight uppercase">Dokumen Tidak Ditemukan</h1>
            <p class="text-rose-100 text-xs font-bold mt-1 opacity-80">Nomor: {{ $number ?? 'Tidak Diketahui' }}</p>
        </div>

        <!-- Content -->
        <div class="p-8 space-y-6 text-center">
            <p class="text-slate-600 font-medium">
                Maaf, sistem tidak dapat menemukan data untuk nomor dokumen tersebut. Harap pastikan Anda memindai kode QR dari dokumen resmi yang diterbitkan oleh SIPANDA Kota Baubau.
            </p>
            
            <a href="/" class="inline-block px-6 py-3 bg-slate-800 text-white rounded-xl font-black text-xs uppercase tracking-widest shadow-lg shadow-slate-500/20 active:scale-95 transition-all">
                Kembali ke Beranda
            </a>
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
