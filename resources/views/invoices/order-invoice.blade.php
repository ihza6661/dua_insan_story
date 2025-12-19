<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $order->order_number }}</title>

    <style>
        @font-face {
            font-family: 'Jost';
            src: url('{{ public_path('fonts/Jost-Regular.ttf') }}') format('truetype');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }

        @font-face {
            font-family: 'Jost';
            src: url('{{ public_path('fonts/Jost-SemiBold.ttf') }}') format('truetype');
            font-weight: 600;
            font-style: normal;
            font-display: swap;
        }

        @font-face {
            font-family: 'Jost';
            src: url('{{ public_path('fonts/Jost-Bold.ttf') }}') format('truetype');
            font-weight: 700;
            font-style: normal;
            font-display: swap;
        }

        @page {
            size: A4;
            margin: 20mm 22mm;
        }

        /* ================= BASE ================= */
        body {
            /* font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; */
            font-family: 'Jost', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 14px;
            line-height: 1.45;
            color: #2d2d2d;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        /* ================= HELPERS ================= */
        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .text-muted {
            color: #777;
        }

        .uppercase {
            text-transform: uppercase;
        }

        .no-wrap {
            white-space: nowrap;
        }

        /* ================= BRAND ================= */
        .brand {
            color: #b89968;
        }

        .divider {
            height: 1px;
            background: #e8e0d8;
            margin: 20px 0;
        }

        /* ================= HEADER ================= */
        .header-title {
            font-size: 32px;
            font-weight: 300;
            letter-spacing: 2px;
        }

        .company-name {
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 1px;
            margin-top: 6px;
        }

        .company-details {
            font-size: 11px;
            color: #777;
            margin-top: 6px;
            line-height: 1.6;
        }

        /* ================= INVOICE META ================= */
        .invoice-label {
            font-size: 10px;
            letter-spacing: 0.5px;
            color: #b89968;
            text-transform: uppercase;
        }

        .invoice-number {
            font-size: 15px;
            font-weight: 600;
        }

        /* meta spacing */
        .meta-group>div {
            margin-top: 6px;
        }

        .meta-group>div:first-child {
            margin-top: 0;
        }

        .meta-group .status {
            margin-top: 6px;
            font-weight: 600;
        }

        /* ================= INFO ================= */
        .info-label {
            font-size: 10px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #b89968;
            margin-bottom: 6px;
        }

        /* ================= PRODUCT TABLE ================= */
        .table-custom th {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 8px 8px;
            border-bottom: 1px solid #2d2d2d;
        }

        .table-custom td {
            padding: 8px 8px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }

        /* DESKRIPSI */
        .table-custom th:nth-child(1),
        .table-custom td:nth-child(1) {
            text-align: left;
        }

        /* QTY */
        .table-custom th:nth-child(2),
        .table-custom td:nth-child(2) {
            text-align: center;
        }

        /* TOTAL */
        .table-custom th:nth-child(3),
        .table-custom td:nth-child(3) {
            text-align: right;
            padding-left: 14px;
        }

        /* product detail */
        .product-name {
            font-weight: 600;
        }

        .product-variant {
            font-size: 12px;
            color: #777;
            margin-top: 3px;
        }

        /* ================= SUMMARY ================= */
        .summary-table {
            width: 50%;
            float: right;
            margin-top: 15px;
            page-break-inside: avoid;
        }

        .summary-table td {
            padding: 4px 0;
        }

        .summary-label {
            font-size: 13px;
            color: #777;
            text-align: right;
            padding-right: 15px;
        }

        .summary-value {
            font-weight: 600;
            text-align: right;
            white-space: nowrap;
        }

        /* spacing before total */
        .summary-table tr:first-child td {
            padding-bottom: 6px;
        }

        /* TOTAL */
        .total-row td {
            padding-top: 12px;
            border-top: 2px solid #2d2d2d;
        }

        .total-label {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .total-amount {
            font-size: 22px;
            font-weight: 700;
            color: #b89968;
        }

        /* ================= NOTE & FOOTER ================= */
        .note {
            font-size: 11px;
            color: #777;
            margin-top: 18px;
            line-height: 1.4;
        }

        .footer {
            margin-top: 12px;
            font-size: 11px;
            color: #aaa;
            text-align: center;
        }
    </style>
</head>

<body>

    {{-- ================= HEADER ================= --}}
    <table>
        <tr>
            <td width="60%">
                <div class="header-title">INVOICE</div>
                <div class="company-name brand">
                    {{ $settings['company_name'] ?? 'Dua Insan Story' }}
                </div>
                <div class="company-details">
                    {{ $settings['company_address'] ?? 'Pontianak, Indonesia' }}<br>
                    +62 813-4936-3547 • duainsanstory@gmail.com
                </div>
            </td>
            <td width="40%" class="text-right">
                <div class="meta-group">
                    <div class="invoice-label">Invoice No.</div>
                    <div class="invoice-number">#{{ $order->order_number }}</div>
                    <div class="text-muted">
                        {{ $order->created_at->format('d M Y') }}
                    </div>

                    <div class="invoice-label status">
                        Status: {{ strtoupper($order->payment_status) }}
                    </div>
                </div>
            </td>

        </tr>
    </table>

    <div class="divider"></div>

    {{-- ================= BILL TO & EVENT ================= --}}
    <table>
        <tr>
            <td width="50%">
                <div class="info-label">Ditagihkan Kepada</div>
                <strong>{{ $order->customer->full_name ?? 'Guest Customer' }}</strong><br>
                {{ $order->customer->email ?? '-' }}<br>
                {{ $order->customer->phone_number ?? '-' }}
            </td>

            @if ($order->invitationDetail)
                <td width="50%" class="text-right">
                    <div class="info-label">Detail Acara</div>
                    <strong>
                        {{ $order->invitationDetail->bride_full_name }} &
                        {{ $order->invitationDetail->groom_full_name }}
                    </strong><br>
                    Akad: {{ \Carbon\Carbon::parse($order->invitationDetail->akad_date)->format('d/m/Y') }}<br>
                    Resepsi: {{ \Carbon\Carbon::parse($order->invitationDetail->reception_date)->format('d/m/Y') }}
                </td>
            @endif
        </tr>
    </table>

    <div class="divider"></div>

    {{-- ================= PRODUCTS ================= --}}
    <table class="table-custom">
        <thead>
            <tr>
                <th width="55%">Deskripsi</th>
                <th width="15%">Jumlah</th>
                <th width="30%">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>
                        <div class="product-name">{{ $item->product->name }}</div>
                        @if ($item->variant)
                            <div class="product-variant">
                                {{ $item->variant->options->pluck('value')->join(', ') }}
                            </div>
                        @endif
                    </td>
                    <td>{{ $item->quantity }}</td>
                    <td class="no-wrap">
                        Rp {{ number_format($item->sub_total, 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ================= SUMMARY ================= --}}
    @php
        $vatRate = $settings['vat_rate'] ?? 0;
        $discount = $order->discount_amount ?? 0;
        $subtotal = $order->total_amount - $discount;
        $vatAmount = $vatRate > 0 ? $subtotal * ($vatRate / 100) : 0;
        $grandTotal = $subtotal + $vatAmount;
    @endphp

    <table class="summary-table">
        <tr>
            <td class="summary-label">Subtotal</td>
            <td class="summary-value">
                Rp {{ number_format($order->total_amount, 0, ',', '.') }}
            </td>
        </tr>

        @if ($discount > 0)
            <tr>
                <td class="summary-label">Diskon</td>
                <td class="summary-value" style="color:#c0392b;">
                    - Rp {{ number_format($discount, 0, ',', '.') }}
                </td>
            </tr>
        @endif

        @if ($vatAmount > 0)
            <tr>
                <td class="summary-label">PPN ({{ $vatRate }}%)</td>
                <td class="summary-value">
                    Rp {{ number_format($vatAmount, 0, ',', '.') }}
                </td>
            </tr>
        @endif

        <tr class="total-row">
            <td class="summary-label total-label">Total Tagihan</td>
            <td class="summary-value">
                <span class="total-amount">
                    Rp {{ number_format($grandTotal, 0, ',', '.') }}
                </span>
            </td>
        </tr>

    </table>

    <div style="clear: both;"></div>

    {{-- ================= NOTE & FOOTER ================= --}}
    <div class="note">
        Invoice ini dibuat secara otomatis dan sah tanpa tanda tangan.<br>
        Terima kasih telah mempercayakan undangan Anda kepada
        <strong>Dua Insan Story</strong>.
    </div>

    <div class="footer">
        © {{ date('Y') }} Dua Insan Story
    </div>

</body>

</html>
