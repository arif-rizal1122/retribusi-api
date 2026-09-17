<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - M-PAD Kota Baubau</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #0F2547;
            background-image: radial-gradient(#1e3a8a 1px, transparent 1px);
            background-size: 24px 24px;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.25rem;
        }
        .v-container {
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            border-radius: 1.75rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .v-header-valid {
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
            color: #ffffff;
            padding: 2rem 1.5rem 1.75rem 1.5rem;
            text-align: center;
            position: relative;
        }
        .v-header-invalid {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: #ffffff;
            padding: 2rem 1.5rem 1.75rem 1.5rem;
            text-align: center;
        }
        .icon-circle {
            width: 58px;
            height: 58px;
            background: rgba(255, 255, 255, 0.2);
            border: 1.5px solid rgba(255, 255, 255, 0.4);
            border-radius: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.75rem auto;
        }
        .icon-circle svg {
            width: 32px;
            height: 32px;
            max-width: 32px;
            max-height: 32px;
            display: block;
            color: #ffffff;
        }
        .v-instansi {
            font-size: 0.65rem;
            font-weight: 800;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.85);
            margin-bottom: 0.25rem;
        }
        .v-title {
            font-size: 1.25rem;
            font-weight: 900;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            line-height: 1.2;
        }
        .v-subtitle {
            font-size: 0.75rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.9);
            margin-top: 0.35rem;
        }
        .v-badge-pill {
            display: inline-block;
            background: #dbeafe;
            color: #1e40af;
            border: 1.5px solid #93c5fd;
            font-size: 0.7rem;
            font-weight: 900;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 0.35rem 1rem;
            border-radius: 9999px;
            margin-top: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .v-body {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            background: #ffffff;
        }
        .v-card-inner {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 1rem;
        }
        .v-label {
            font-size: 0.625rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #64748b;
            margin-bottom: 0.25rem;
        }
        .v-val-bold {
            font-size: 0.95rem;
            font-weight: 800;
            color: #0f172a;
            word-break: break-all;
        }
        .v-val-code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.95rem;
            font-weight: 800;
            color: #1e40af;
            letter-spacing: 0.05em;
            word-break: break-all;
        }
        .v-val-sub {
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            margin-top: 0.15rem;
        }
        .v-status-box-paid {
            background: #f0fdf4;
            border: 1.5px solid #86efac;
            border-radius: 1rem;
            padding: 1rem;
            text-align: center;
        }
        .v-status-box-pending {
            background: #fffbeb;
            border: 1.5px solid #fde68a;
            border-radius: 1rem;
            padding: 1rem;
            text-align: center;
        }
        .v-status-title-paid {
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #166534;
            margin-bottom: 0.25rem;
        }
        .v-status-title-pending {
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #92400e;
            margin-bottom: 0.25rem;
        }
        .v-status-desc-paid {
            font-size: 0.9rem;
            font-weight: 900;
            color: #15803d;
            text-transform: uppercase;
        }
        .v-status-desc-pending {
            font-size: 0.9rem;
            font-weight: 900;
            color: #b45309;
            text-transform: uppercase;
        }
        .v-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
            padding: 0.35rem 0;
        }
        .v-divider {
            height: 1px;
            background: #e2e8f0;
            margin: 0.5rem 0;
        }
        .v-legal-notice {
            font-size: 0.65rem;
            line-height: 1.4;
            color: #94a3b8;
            text-align: center;
            font-style: italic;
        }
        .v-footer {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .v-footer-icon {
            width: 20px;
            height: 20px;
            background: #2563eb;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
        }
        .v-footer-icon svg {
            width: 12px;
            height: 12px;
            max-width: 12px;
            max-height: 12px;
        }
        .v-footer-text {
            font-size: 0.65rem;
            font-weight: 900;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="v-container">
        <!-- Header -->
        @if($is_valid)
        <div class="v-header-valid">
            <div class="icon-circle">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    <path d="m9 12 2 2 4-4"/>
                </svg>
            </div>
            <div class="v-instansi">Pemerintah Kota Baubau</div>
            <h1 class="v-title">Dokumen Terverifikasi</h1>
            <p class="v-subtitle">{{ $doc_label ?? 'Dokumen Resmi M-PAD' }}</p>
            <div class="v-badge-pill">✓ Sah Terdaftar di V-TAX</div>
        </div>
        @else
        <div class="v-header-invalid">
            <div class="icon-circle">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </div>
            <div class="v-instansi">Pemerintah Kota Baubau</div>
            <h1 class="v-title">Dokumen Tidak Ditemukan</h1>
            <p class="v-subtitle">Nomor: {{ $number ?? 'Tidak Valid' }}</p>
        </div>
        @endif

        <!-- Body -->
        <div class="v-body">
            <div class="v-card-inner">
                <div class="v-label">Nomor Dokumen</div>
                <div class="v-val-code">{{ $number }}</div>
            </div>

            <div class="v-card-inner">
                <div class="v-label">Jenis Dokumen</div>
                <div class="v-val-bold">{{ $doc_label }}</div>
            </div>

            @if($bill)
            <div class="v-card-inner">
                <div class="v-label">Wajib Pajak / Wajib Retribusi</div>
                <div class="v-val-bold">{{ $bill->taxpayer->name ?? '-' }}</div>
                <div class="v-val-sub">NPWPD/NIK: {{ $bill->taxpayer->npwpd ?? ($bill->taxpayer->taxpayer_id ?? '-') }}</div>
            </div>

            <div class="v-card-inner">
                <div class="v-label">Rincian Nilai Ketetapan</div>
                <div class="v-row">
                    <span style="color: #64748b;">Pokok</span>
                    <span style="font-weight: 700;">Rp {{ number_format($bill->amount, 0, ',', '.') }}</span>
                </div>
                @if(($bill->penalty_amount ?? 0) > 0)
                <div class="v-row">
                    <span style="color: #dc2626;">Denda</span>
                    <span style="font-weight: 700; color: #dc2626;">Rp {{ number_format($bill->penalty_amount, 0, ',', '.') }}</span>
                </div>
                @endif
                <div class="v-divider"></div>
                <div class="v-row">
                    <span style="font-weight: 900; text-transform: uppercase; font-size: 0.85rem;">Total</span>
                    <span style="font-weight: 900; font-size: 1.15rem; color: #1e40af;">Rp {{ number_format($bill->total_amount ?? ($bill->amount + ($bill->penalty_amount ?? 0)), 0, ',', '.') }}</span>
                </div>
            </div>
            @endif

            @if(isset($payment))
            <div class="v-status-box-paid">
                <div class="v-status-title-paid">Status Pelunasan Kas Daerah</div>
                <div class="v-status-desc-paid">LUNAS PADA {{ $payment->paid_at ? $payment->paid_at->format('d/m/Y H:i') : ($payment->created_at ? $payment->created_at->format('d/m/Y H:i') : '-') }}</div>
            </div>
            @elseif($bill)
            <div class="v-status-box-pending">
                <div class="v-status-title-pending">Status Ketetapan</div>
                <div class="v-status-desc-pending">MENUNGGU PEMBAYARAN</div>
            </div>
            @endif

            <p class="v-legal-notice">
                * Data diambil secara real-time dari V-TAX Engine & Kas Daerah M-PAD Kota Baubau. Keaslian dokumen dijamin sah secara hukum.
            </p>
        </div>

        <!-- Footer -->
        <div class="v-footer">
            <div class="v-footer-icon">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </div>
            <span class="v-footer-text">Verified by V-TAX Engine • Baubau</span>
        </div>
    </div>
</body>
</html>
