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
            background: linear-gradient(135deg, #15803d 0%, #166534 100%);
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
            background: #dcfce7;
            color: #15803d;
            border: 1.5px solid #86efac;
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
            gap: 1rem;
            background: #ffffff;
        }
        .v-section {
            background: #f8fafc;
            border-radius: 1rem;
            padding: 1rem;
            border: 1px solid #e2e8f0;
        }
        .v-label {
            font-size: 0.625rem;
            font-weight: 900;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 0.25rem;
        }
        .v-value-lg {
            font-size: 1.05rem;
            font-weight: 900;
            color: #0f172a;
            line-height: 1.3;
        }
        .v-value-mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.75rem;
            font-weight: 700;
            color: #3b82f6;
            letter-spacing: 0.02em;
        }
        .v-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }
        .v-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.75rem;
            margin-bottom: 0.35rem;
        }
        .v-row:last-child {
            margin-bottom: 0;
        }
        .v-row-label {
            color: #64748b;
            font-weight: 500;
        }
        .v-row-val {
            font-weight: 700;
            color: #1e293b;
        }
        .v-total {
            border-top: 1px dashed #cbd5e1;
            padding-top: 0.6rem;
            margin-top: 0.6rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .v-total-label {
            font-size: 0.75rem;
            font-weight: 900;
            text-transform: uppercase;
            color: #0f172a;
        }
        .v-total-value {
            font-size: 1.15rem;
            font-weight: 900;
            color: #15803d;
        }
        .v-status-box {
            padding: 0.9rem;
            border-radius: 0.9rem;
            text-align: center;
            border: 1px solid #bbf7d0;
            background: #f0fdf4;
        }
        .v-status-title {
            font-size: 0.625rem;
            font-weight: 900;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #166534;
            margin-bottom: 0.2rem;
        }
        .v-status-text {
            font-size: 0.85rem;
            font-weight: 900;
            color: #15803d;
            text-transform: uppercase;
        }
        .v-footer {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 0.65rem;
            font-weight: 900;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #64748b;
        }
        .v-footer-icon {
            width: 16px;
            height: 16px;
            max-width: 16px;
            max-height: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #0F2547;
            color: #ffffff;
            border-radius: 4px;
            padding: 2px;
        }
        .v-footer-icon svg {
            width: 12px;
            height: 12px;
            max-width: 12px;
            max-height: 12px;
            display: block;
        }
        .v-disclaimer {
            font-size: 0.625rem;
            color: #94a3b8;
            text-align: center;
            line-height: 1.4;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="v-container">
        <!-- Header -->
        <div class="{{ $is_valid ? 'v-header-valid' : 'v-header-invalid' }}">
            <p class="v-instansi">PEMERINTAH KOTA BAUBAU</p>
            <div class="icon-circle">
                @if($is_valid)
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 6 9 17l-5-5"/>
                    </svg>
                @else
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                    </svg>
                @endif
            </div>
            <h1 class="v-title">{{ $is_valid ? 'DOKUMEN VALID' : 'DOKUMEN TIDAK VALID' }}</h1>
            <p class="v-subtitle">{{ $title }}</p>
            @if($is_valid)
                <div>
                    <span class="v-badge-pill">✓ LUNAS 100% RKUD</span>
                </div>
            @endif
        </div>

        <!-- Content -->
        <div class="v-body">
            <!-- Data WP -->
            <div class="v-section">
                <p class="v-label">Wajib Pajak / Retribusi</p>
                <p class="v-value-lg">{{ $bill->taxpayer->name ?? 'Wajib Pajak' }}</p>
                @if(!empty($bill->taxpayer->npwpd) || !empty($bill->taxpayer->nik))
                    <p class="v-value-mono" style="margin-top: 2px;">{{ $bill->taxpayer->npwpd ?: $bill->taxpayer->nik }}</p>
                @endif
                @if($bill->taxObject)
                    <p class="v-row-label" style="font-size: 0.7rem; margin-top: 4px;">Objek: <strong style="color: #334155;">{{ $bill->taxObject->name }}</strong></p>
                @endif
            </div>

            <!-- Identitas Tagihan -->
            <div class="v-grid-2">
                <div class="v-section">
                    <p class="v-label">Nomor Dokumen</p>
                    <p class="v-value-mono">{{ $bill->bill_number }}</p>
                </div>
                <div class="v-section">
                    <p class="v-label">Masa / Periode</p>
                    <p class="v-row-val">{{ $bill->period ?: date('Y') }}</p>
                </div>
            </div>

            <!-- Rincian Biaya -->
            <div class="v-section">
                <p class="v-label" style="margin-bottom: 0.5rem;">Rincian Pembayaran</p>
                <div class="v-row">
                    <span class="v-row-label">Pokok</span>
                    <span class="v-row-val">Rp {{ number_format($bill->amount, 0, ',', '.') }}</span>
                </div>
                @if($bill->penalty_amount > 0)
                <div class="v-row">
                    <span class="v-row-label">Sanksi Bunga / Denda</span>
                    <span class="v-row-val">Rp {{ number_format($bill->penalty_amount, 0, ',', '.') }}</span>
                </div>
                @endif
                @if($bill->fixed_fine_amount > 0)
                <div class="v-row">
                    <span class="v-row-label">Denda Administrasi</span>
                    <span class="v-row-val">Rp {{ number_format($bill->fixed_fine_amount, 0, ',', '.') }}</span>
                </div>
                @endif
                <div class="v-total">
                    <span class="v-total-label">TOTAL DILUNASI</span>
                    <span class="v-total-value">Rp {{ number_format($bill->total_amount, 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- Status Pembayaran Box -->
            @if(isset($payment) && $payment)
            <div class="v-status-box">
                <p class="v-status-title">Status Pembayaran</p>
                <p class="v-status-text">
                    LUNAS PADA {{ $payment->paid_at ? $payment->paid_at->format('d/m/Y H:i') . ' WITA' : date('d/m/Y H:i') . ' WITA' }}
                </p>
                @if($payment->channel || $payment->receipt_number)
                    <p style="font-size: 0.65rem; color: #475569; margin-top: 3px; font-family: monospace;">
                        Ref/NTPD: {{ $payment->receipt_number ?: $payment->transaction_id }}
                    </p>
                @endif
            </div>
            @else
            <div class="v-status-box">
                <p class="v-status-title">Status Pembayaran</p>
                <p class="v-status-text">LUNAS TERCATAT PADA SISTEM</p>
            </div>
            @endif

            <p class="v-disclaimer">
                * Data ini diambil secara realtime dari sistem M-PAD Kota Baubau. Keaslian dokumen dijamin sah secara hukum.
            </p>
        </div>

        <!-- Footer -->
        <div class="v-footer">
            <span class="v-footer-icon">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 6 9 17l-5-5"/>
                </svg>
            </span>
            <span>VERIFIED BY V-TAX ENGINE · KOTA BAUBAU</span>
        </div>
    </div>
</body>
</html>
