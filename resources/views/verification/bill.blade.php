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
        .v-header-paid {
            background: linear-gradient(135deg, #15803d 0%, #166534 100%);
            color: #ffffff;
            padding: 2rem 1.5rem 1.75rem 1.5rem;
            text-align: center;
        }
        .v-header-unpaid {
            background: linear-gradient(135deg, #0F2547 0%, #1e3a8a 100%);
            color: #ffffff;
            padding: 2rem 1.5rem 1.75rem 1.5rem;
            text-align: center;
        }
        .v-header-overdue {
            background: linear-gradient(135deg, #be123c 0%, #9f1239 100%);
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
            font-size: 0.7rem;
            font-weight: 900;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 0.35rem 1rem;
            border-radius: 9999px;
            margin-top: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .v-badge-green {
            background: #dcfce7;
            color: #15803d;
            border: 1.5px solid #86efac;
        }
        .v-badge-amber {
            background: #fef3c7;
            color: #92400e;
            border: 1.5px solid #fde68a;
        }
        .v-badge-rose {
            background: #ffe4e6;
            color: #9f1239;
            border: 1.5px solid #fecdd3;
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
            color: #0F2547;
        }
        .v-status-box {
            padding: 0.9rem;
            border-radius: 0.9rem;
            text-align: center;
            border: 1px solid transparent;
        }
        .v-status-paid {
            background: #f0fdf4;
            border-color: #bbf7d0;
            color: #15803d;
        }
        .v-status-unpaid {
            background: #fffbeb;
            border-color: #fde68a;
            color: #92400e;
        }
        .v-status-overdue {
            background: #fff1f2;
            border-color: #fecdd3;
            color: #be123c;
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
    @php
        $sudahLunas = in_array(strtolower($bill->status), ['lunas', 'paid', 'settled'], true);
        $isOverdue = strtolower($bill->status) === 'overdue';
        $headerClass = $sudahLunas ? 'v-header-paid' : ($isOverdue ? 'v-header-overdue' : 'v-header-unpaid');
        $waktuBayar = isset($payment) && $payment && $payment->paid_at ? $payment->paid_at : null;
    @endphp

    <div class="v-container">
        <!-- Header -->
        <div class="{{ $headerClass }}">
            <p class="v-instansi">PEMERINTAH KOTA BAUBAU</p>
            <div class="icon-circle">
                @if($sudahLunas)
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 6 9 17l-5-5"/>
                    </svg>
                @else
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                @endif
            </div>
            <h1 class="v-title">{{ $is_valid ? 'DOKUMEN VALID' : 'DOKUMEN TIDAK VALID' }}</h1>
            <p class="v-subtitle">{{ $title }}</p>
            <div>
                @if($sudahLunas)
                    <span class="v-badge-pill v-badge-green">✓ LUNAS 100% RKUD</span>
                @elseif($isOverdue)
                    <span class="v-badge-pill v-badge-rose">⚠ TERLAMBAT</span>
                @else
                    <span class="v-badge-pill v-badge-amber">⏳ MENUNGGU PEMBAYARAN</span>
                @endif
            </div>
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
                <p class="v-label" style="margin-bottom: 0.5rem;">Rincian Ketetapan Tagihan</p>
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
                    <span class="v-total-label">TOTAL TAGIHAN</span>
                    <span class="v-total-value" style="color: {{ $sudahLunas ? '#15803d' : '#0F2547' }};">Rp {{ number_format($bill->total_amount, 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- Status Box -->
            @if($sudahLunas)
            <div class="v-status-box v-status-paid">
                <p class="v-label" style="color: #166534;">Status Pembayaran</p>
                <p style="font-size: 0.85rem; font-weight: 900; color: #15803d;">
                    LUNAS{{ $waktuBayar ? ' PADA ' . $waktuBayar->format('d/m/Y H:i') . ' WITA' : ' DI KAS DAERAH' }}
                </p>
            </div>
            @elseif($isOverdue)
            <div class="v-status-box v-status-overdue">
                <p class="v-label" style="color: #9f1239;">Status Tagihan</p>
                <p style="font-size: 0.85rem; font-weight: 900; color: #be123c;">TERLAMBAT — HARAP SEGERA BAYAR</p>
            </div>
            @else
            <div class="v-status-box v-status-unpaid">
                <p class="v-label" style="color: #92400e;">Status Tagihan</p>
                <p style="font-size: 0.85rem; font-weight: 900; color: #d97706;">MENUNGGU PEMBAYARAN</p>
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
