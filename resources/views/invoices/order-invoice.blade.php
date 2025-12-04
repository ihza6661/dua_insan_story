<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $order->order_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            line-height: 1.3;
            color: #3d2e28;
            background-color: #faf9f8;
        }
        
        .container { padding: 15px 20px; }
        
        /* Header */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 2px solid #b89968;
        }
        
        .header-left {
            display: table-cell;
            width: 55%;
            vertical-align: top;
        }
        
        .header-right {
            display: table-cell;
            width: 45%;
            text-align: right;
            vertical-align: top;
        }
        
        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #3d2e28;
            margin-bottom: 2px;
        }
        
        .company-tagline {
            font-size: 7px;
            color: #b89968;
            font-style: italic;
            margin-bottom: 3px;
        }
        
        .company-info {
            font-size: 7px;
            color: #796656;
            line-height: 1.4;
        }
        
        .invoice-title {
            font-size: 16px;
            font-weight: bold;
            color: #3d2e28;
            margin-bottom: 2px;
        }
        
        .invoice-number {
            font-size: 10px;
            color: #796656;
            margin-bottom: 3px;
        }
        
        .invoice-date {
            font-size: 7px;
            color: #796656;
            line-height: 1.6;
        }
        
        .invoice-status {
            margin-top: 4px;
        }
        
        /* Sections */
        .section { margin-bottom: 8px; }
        
        .section-title {
            font-size: 9px;
            font-weight: bold;
            color: #b89968;
            margin-bottom: 4px;
            padding-bottom: 2px;
            border-bottom: 1px solid #e8e0d8;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .info-grid {
            display: table;
            width: 100%;
        }
        
        .info-column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 8px;
        }
        
        .info-row {
            margin-bottom: 3px;
            font-size: 8px;
        }
        
        .info-label {
            font-weight: bold;
            color: #796656;
            display: inline-block;
            min-width: 70px;
        }
        
        .info-value {
            color: #3d2e28;
        }
        
        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        
        table thead {
            background-color: #3d2e28;
            color: #faf9f8;
        }
        
        table th {
            padding: 5px 4px;
            text-align: left;
            font-weight: bold;
            font-size: 8px;
            text-transform: uppercase;
        }
        
        table td {
            padding: 5px 4px;
            border-bottom: 1px solid #e8e0d8;
            font-size: 8px;
        }
        
        table tbody tr:last-child td {
            border-bottom: 2px solid #b89968;
        }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        /* Summary */
        .summary-section {
            margin-top: 8px;
            float: right;
            width: 50%;
        }
        
        .summary-row {
            display: table;
            width: 100%;
            margin-bottom: 3px;
        }
        
        .summary-label {
            display: table-cell;
            width: 60%;
            text-align: right;
            padding-right: 15px;
            font-size: 8px;
            color: #796656;
        }
        
        .summary-value {
            display: table-cell;
            width: 40%;
            text-align: right;
            font-size: 8px;
            color: #3d2e28;
        }
        
        .summary-total {
            border-top: 2px solid #3d2e28;
            padding-top: 4px;
            margin-top: 4px;
        }
        
        .summary-total .summary-label {
            font-weight: bold;
            font-size: 9px;
            color: #3d2e28;
        }
        
        .summary-total .summary-value {
            font-weight: bold;
            font-size: 10px;
            color: #b89968;
        }
        
        .payment-info {
            clear: both;
            background-color: #f5f0ea;
            padding: 8px;
            border-radius: 3px;
            border-left: 3px solid #b89968;
            margin-top: 10px;
            font-size: 8px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 8px;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-paid { background-color: #d4edda; color: #155724; }
        .status-partial { background-color: #fff3cd; color: #856404; }
        .status-pending { background-color: #f8d7da; color: #721c24; }
        
        .footer {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid #e8e0d8;
            text-align: center;
            font-size: 7px;
            color: #796656;
        }
        
        .footer-note {
            margin-bottom: 5px;
            font-style: italic;
        }
        
        .product-name {
            font-weight: bold;
            color: #3d2e28;
            font-size: 8px;
        }
        
        .product-variant {
            font-size: 7px;
            color: #796656;
            margin-top: 1px;
        }
        
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <div class="company-name">{{ $settings['company_name'] ?? 'Dua Insan Story' }}</div>
                <div class="company-tagline">Wujudkan Undangan Impian Anda</div>
                <div class="company-info">
                    {{ $settings['company_address'] ?? 'Indonesia' }}<br>
                    Tel: +62 813-4936-3547 | Email: {{ $settings['company_email'] ?? '-' }}
                </div>
            </div>
            <div class="header-right">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-number">#{{ $order->order_number }}</div>
                <div class="invoice-date">
                    <strong>Tanggal:</strong> {{ $order->created_at->format('d M Y') }}
                </div>
                <div class="invoice-status">
                    @if($order->payment_status === 'paid')
                        <span class="status-badge status-paid">Lunas</span>
                    @elseif($order->payment_status === 'partially_paid')
                        <span class="status-badge status-partial">Sebagian</span>
                    @else
                        <span class="status-badge status-pending">Pending</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Customer & Wedding Info (Combined) -->
        <div class="section">
            <div class="section-title">Informasi Pelanggan & Pernikahan</div>
            <div class="info-grid">
                <div class="info-column">
                    <div class="info-row">
                        <span class="info-label">Nama:</span>
                        <span class="info-value">{{ $order->customer->full_name ?? 'Guest' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value">{{ $order->customer->email ?? '-' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Telepon:</span>
                        <span class="info-value">{{ $order->customer->phone_number ?? '-' }}</span>
                    </div>
                </div>
                <div class="info-column">
                    @if($order->invitationDetail)
                    <div class="info-row">
                        <span class="info-label">Mempelai:</span>
                        <span class="info-value">{{ $order->invitationDetail->bride_full_name }} & {{ $order->invitationDetail->groom_full_name }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Akad:</span>
                        <span class="info-value">{{ \Carbon\Carbon::parse($order->invitationDetail->akad_date)->format('d M Y') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Resepsi:</span>
                        <span class="info-value">{{ \Carbon\Carbon::parse($order->invitationDetail->reception_date)->format('d M Y') }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="section">
            <div class="section-title">Detail Pesanan</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 45%;">Produk</th>
                        <th style="width: 12%;" class="text-center">Qty</th>
                        <th style="width: 19%;" class="text-right">Harga</th>
                        <th style="width: 19%;" class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $index => $item)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>
                            <div class="product-name">{{ $item->product->name }}</div>
                            @if($item->variant)
                            <div class="product-variant">
                                Varian: 
                                @if($item->variant->options && count($item->variant->options) > 0)
                                    {{ collect($item->variant->options)->pluck('value')->implode(', ') }}
                                @else
                                    Default
                                @endif
                            </div>
                            @endif
                        </td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format($item->sub_total, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Summary -->
        <div class="clearfix">
            <div class="summary-section">
                <div class="summary-row">
                    <div class="summary-label">Subtotal:</div>
                    <div class="summary-value">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</div>
                </div>
                
                @if(isset($order->discount_amount) && $order->discount_amount > 0)
                <div class="summary-row">
                    <div class="summary-label">Diskon:</div>
                    <div class="summary-value">- Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</div>
                </div>
                @endif
                
                @php
                    $vatRate = $settings['vat_rate'] ?? null;
                    $vatAmount = 0;
                    if ($vatRate && $vatRate > 0) {
                        $subtotal = $order->total_amount - ($order->discount_amount ?? 0);
                        $vatAmount = $subtotal * ($vatRate / 100);
                    }
                @endphp
                
                @if($vatAmount > 0)
                <div class="summary-row">
                    <div class="summary-label">PPN ({{ $vatRate }}%):</div>
                    <div class="summary-value">Rp {{ number_format($vatAmount, 0, ',', '.') }}</div>
                </div>
                @endif
                
                <div class="summary-row summary-total">
                    <div class="summary-label">Total:</div>
                    <div class="summary-value">Rp {{ number_format($order->total_amount + $vatAmount, 0, ',', '.') }}</div>
                </div>
                
                <div class="summary-row">
                    <div class="summary-label">Dibayar:</div>
                    <div class="summary-value">Rp {{ number_format($order->amount_paid ?? 0, 0, ',', '.') }}</div>
                </div>
                
                @if($order->payment_status !== 'paid')
                <div class="summary-row">
                    <div class="summary-label">Sisa:</div>
                    <div class="summary-value">Rp {{ number_format(max(0, ($order->total_amount + $vatAmount) - ($order->amount_paid ?? 0)), 0, ',', '.') }}</div>
                </div>
                @endif
            </div>
        </div>

        <!-- Payment Info -->
        <div class="payment-info">
            <strong>Metode Pembayaran:</strong> 
            @if($order->payment_option === 'full')
                Pembayaran Penuh
            @elseif($order->payment_option === 'dp_30')
                DP 30% (Rp {{ number_format($order->total_amount * 0.3, 0, ',', '.') }})
            @elseif($order->payment_option === 'dp_50')
                DP 50% (Rp {{ number_format($order->total_amount * 0.5, 0, ',', '.') }})
            @else
                {{ ucfirst($order->payment_option ?? 'Unknown') }}
            @endif
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-note">Terima kasih atas kepercayaan Anda pada Dua Insan Story</div>
            <div>Invoice ini dibuat secara otomatis dan sah tanpa tanda tangan | {{ now()->format('d M Y H:i') }}</div>
        </div>
    </div>
</body>
</html>
