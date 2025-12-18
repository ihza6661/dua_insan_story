<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $order->order_number }}</title>
    <style>
        /* Reset & Base */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        table { border-collapse: collapse !important; }
        body { height: 100% !important; margin: 0 !important; padding: 0 !important; width: 100% !important; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #faf9f8; color: #3d2e28; }
        
        /* Utility */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
        .text-gold { color: #b89968; }
        .text-sm { font-size: 12px; }
        .mt-2 { margin-top: 10px; }
        .mb-2 { margin-bottom: 10px; }
        
        /* Specifics */
        .wrapper { width: 100%; table-layout: fixed; background-color: #faf9f8; padding-bottom: 40px; }
        .main-container { background-color: #ffffff; margin: 0 auto; width: 100%; max-width: 600px; border-top: 4px solid #b89968; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .content-padding { padding: 30px; }
        
        /* Header */
        .header-title { font-size: 24px; font-weight: bold; color: #3d2e28; margin: 0; }
        .header-subtitle { font-size: 14px; color: #b89968; font-style: italic; margin: 5px 0 0 0; }
        .invoice-tag { background-color: #3d2e28; color: #ffffff; padding: 5px 10px; font-size: 12px; font-weight: bold; display: inline-block; border-radius: 4px; }
        
        /* Info Boxes */
        .info-box { background-color: #fcfbf9; border: 1px solid #e8e0d8; padding: 15px; border-radius: 4px; width: 100%; }
        .info-title { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #b89968; font-weight: bold; margin-bottom: 8px; border-bottom: 1px solid #e8e0d8; padding-bottom: 4px; }
        .info-text { font-size: 13px; line-height: 1.5; color: #555555; }
        
        /* Table */
        .product-table { width: 100%; margin-top: 20px; border: 1px solid #e8e0d8; }
        .product-table th { background-color: #3d2e28; color: #ffffff; padding: 10px; font-size: 12px; text-transform: uppercase; text-align: left; }
        .product-table td { padding: 12px 10px; border-bottom: 1px solid #e8e0d8; font-size: 13px; vertical-align: top; }
        .product-name { font-weight: bold; color: #3d2e28; display: block; }
        .product-variant { font-size: 11px; color: #888; margin-top: 2px; }
        
        /* Summary */
        .summary-table { width: 100%; margin-top: 15px; }
        .summary-table td { padding: 5px 0; font-size: 13px; }
        .summary-label { text-align: right; padding-right: 15px; color: #777; width: 70%; }
        .summary-value { text-align: right; color: #3d2e28; width: 30%; }
        .total-row td { border-top: 2px solid #3d2e28; padding-top: 10px; font-weight: bold; font-size: 16px; color: #b89968; }
        
        /* Status Badges */
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .badge-paid { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .badge-partial { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .badge-pending { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* Footer */
        .footer { text-align: center; font-size: 11px; color: #999; margin-top: 30px; line-height: 1.5; }

        /* Mobile Responsive */
        @media screen and (max-width: 600px) {
            .content-padding { padding: 15px; }
            .mobile-stack { display: block !important; width: 100% !important; }
            .mobile-padding { padding-top: 15px; }
            .header-title { font-size: 20px; }
        }
    </style>
</head>
<body>
    <table class="wrapper">
        <tr>
            <td align="center">
                <table class="main-container">
                    <tr>
                        <td class="content-padding">
                            
                            <table width="100%">
                                <tr>
                                    <td width="60%" valign="top">
                                        <h1 class="header-title">{{ $settings['company_name'] ?? 'Dua Insan Story' }}</h1>
                                        <p class="header-subtitle">Wujudkan Undangan Impian Anda</p>
                                        <div style="font-size: 12px; color: #777; margin-top: 8px; line-height: 1.4;">
                                            {{ $settings['company_address'] ?? 'Pontianak, Indonesia' }}<br>
                                            +62 813-4936-3547 | duainsanstory@gmail.com
                                        </div>
                                    </td>
                                    <td width="40%" valign="top" class="text-right">
                                        <div class="invoice-tag">INVOICE</div>
                                        <div style="font-size: 14px; font-weight: bold; margin-top: 5px; color: #555;">#{{ $order->order_number }}</div>
                                        <div style="font-size: 11px; color: #888; margin-top: 2px;">{{ $order->created_at->format('d M Y') }}</div>
                                        <div style="margin-top: 5px;">
                                            @if($order->payment_status === 'paid')
                                                <span class="badge badge-paid">Lunas</span>
                                            @elseif($order->payment_status === 'partially_paid')
                                                <span class="badge badge-partial">Sebagian</span>
                                            @else
                                                <span class="badge badge-pending">Pending</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <div style="height: 25px;"></div>

                            <table width="100%">
                                <tr>
                                    <td width="48%" valign="top" class="mobile-stack">
                                        <div class="info-box">
                                            <div class="info-title">Ditagihkan Kepada</div>
                                            <div class="info-text">
                                                <strong>{{ $order->customer->full_name ?? 'Guest' }}</strong><br>
                                                {{ $order->customer->email ?? '-' }}<br>
                                                {{ $order->customer->phone_number ?? '-' }}
                                            </div>
                                        </div>
                                    </td>
                                    <td width="4%">&nbsp;</td>
                                    <td width="48%" valign="top" class="mobile-stack mobile-padding">
                                        @if($order->invitationDetail)
                                        <div class="info-box">
                                            <div class="info-title">Detail Acara</div>
                                            <div class="info-text">
                                                <strong>{{ $order->invitationDetail->bride_full_name }} & {{ $order->invitationDetail->groom_full_name }}</strong><br>
                                                Akad: {{ \Carbon\Carbon::parse($order->invitationDetail->akad_date)->format('d/m/y') }}<br>
                                                Resepsi: {{ \Carbon\Carbon::parse($order->invitationDetail->reception_date)->format('d/m/y') }}
                                            </div>
                                        </div>
                                        @endif
                                    </td>
                                </tr>
                            </table>

                            <table class="product-table">
                                <thead>
                                    <tr>
                                        <th width="50%">Produk</th>
                                        <th width="15%" class="text-center">Qty</th>
                                        <th width="35%" class="text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($order->items as $item)
                                    <tr>
                                        <td>
                                            <span class="product-name">{{ $item->product->name }}</span>
                                            @if($item->variant)
                                            <div class="product-variant">
                                                @if($item->variant->options && count($item->variant->options) > 0)
                                                    {{ collect($item->variant->options)->pluck('value')->implode(', ') }}
                                                @else
                                                    Default
                                                @endif
                                            </div>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $item->quantity }}</td>
                                        <td class="text-right">Rp {{ number_format($item->sub_total, 0, ',', '.') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <table width="100%" style="margin-top: 20px;">
                                <tr>
                                    <td width="50%" valign="top" class="mobile-stack">
                                        <div style="background: #faf9f8; border-left: 3px solid #b89968; padding: 10px; font-size: 12px; color: #555;">
                                            <div style="margin-bottom: 4px;"><strong>Pembayaran:</strong> {{ $order->getFormattedPaymentOption() }}</div>
                                            @if($order->getFormattedPaymentMethod())
                                            <div><strong>Metode:</strong> {{ $order->getFormattedPaymentMethod() }}</div>
                                            @endif
                                        </div>
                                    </td>
                                    
                                    <td width="50%" valign="top" class="mobile-stack mobile-padding">
                                        <table class="summary-table">
                                            <tr>
                                                <td class="summary-label">Subtotal</td>
                                                <td class="summary-value">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                                            </tr>
                                            @if(isset($order->discount_amount) && $order->discount_amount > 0)
                                            <tr>
                                                <td class="summary-label">Diskon</td>
                                                <td class="summary-value" style="color: #d9534f;">- Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</td>
                                            </tr>
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
                                            <tr>
                                                <td class="summary-label">PPN ({{ $vatRate }}%)</td>
                                                <td class="summary-value">Rp {{ number_format($vatAmount, 0, ',', '.') }}</td>
                                            </tr>
                                            @endif

                                            <tr class="total-row">
                                                <td class="summary-label" style="color: #3d2e28;">Total</td>
                                                <td class="summary-value">Rp {{ number_format($order->total_amount + $vatAmount, 0, ',', '.') }}</td>
                                            </tr>
                                            
                                            @if($order->payment_status !== 'paid')
                                            <tr>
                                                <td class="summary-label">Dibayar</td>
                                                <td class="summary-value">Rp {{ number_format($order->amount_paid ?? 0, 0, ',', '.') }}</td>
                                            </tr>
                                            <tr>
                                                <td class="summary-label" style="font-weight: bold;">Sisa Tagihan</td>
                                                <td class="summary-value" style="font-weight: bold; color: #d9534f;">Rp {{ number_format(max(0, ($order->total_amount + $vatAmount) - ($order->amount_paid ?? 0)), 0, ',', '.') }}</td>
                                            </tr>
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <div class="footer">
                                <p style="margin-bottom: 5px; font-style: italic;">Terima kasih telah mempercayakan momen spesial Anda kepada Dua Insan Story.</p>
                                <p style="font-size: 10px; color: #bbb;">Invoice ini dibuat secara otomatis pada {{ now()->format('d M Y H:i') }}</p>
                            </div>

                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>