<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja Anda</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px 20px;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
        }
        .message {
            font-size: 16px;
            margin-bottom: 20px;
            color: #555;
        }
        .discount-badge {
            background-color: #fbbf24;
            color: #78350f;
            padding: 10px 20px;
            border-radius: 6px;
            display: inline-block;
            font-weight: bold;
            margin: 20px 0;
            font-size: 18px;
        }
        .cart-items {
            background-color: #f9fafb;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .cart-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .cart-item:last-child {
            border-bottom: none;
        }
        .item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 15px;
        }
        .item-details {
            flex: 1;
        }
        .item-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        .item-quantity {
            color: #6b7280;
            font-size: 14px;
        }
        .item-price {
            font-weight: 600;
            color: #667eea;
        }
        .cart-total {
            background-color: #f3f4f6;
            padding: 15px 20px;
            border-radius: 6px;
            margin: 20px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .total-label {
            font-size: 16px;
            font-weight: 600;
        }
        .total-amount {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }
        .cta-button {
            display: block;
            width: 100%;
            text-align: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            margin: 20px 0;
            transition: transform 0.2s;
        }
        .cta-button:hover {
            transform: translateY(-2px);
        }
        .features {
            display: flex;
            justify-content: space-around;
            margin: 30px 0;
            padding: 20px;
            background-color: #f9fafb;
            border-radius: 6px;
        }
        .feature {
            text-align: center;
            flex: 1;
        }
        .feature-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }
        .feature-text {
            font-size: 14px;
            color: #6b7280;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px;
            text-align: center;
            font-size: 14px;
            color: #6b7280;
        }
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
        .unsubscribe {
            margin-top: 15px;
            font-size: 12px;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>Dua Insan Story</h1>
            @if($reminderType === '1h')
                <p>Anda meninggalkan sesuatu! ⏰</p>
            @elseif($reminderType === '24h')
                <p>Undangan impian Anda menunggu! 💝</p>
            @else
                <p>Penawaran spesial untuk Anda! 🎁</p>
            @endif
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">
                Hai {{ $abandonedCart->name ?: 'Pelanggan Setia' }},
            </div>

            @if($reminderType === '1h')
                <div class="message">
                    Kami perhatikan Anda meninggalkan beberapa item di keranjang belanja Anda. 
                    Item-item pilihan Anda masih tersedia dan menunggu untuk melengkapi hari spesial Anda!
                </div>
            @elseif($reminderType === '24h')
                <div class="message">
                    Jangan lewatkan undangan impian Anda! Item pilihan Anda masih tersimpan aman di keranjang. 
                    Ribuan pasangan telah mempercayakan hari spesial mereka bersama kami. 
                    Giliran Anda sekarang! ✨
                </div>
            @else
                <div class="message">
                    Ini kesempatan terakhir! Kami ingin membantu Anda menyelesaikan pesanan dengan memberikan 
                    <strong>diskon khusus 10%</strong> untuk item di keranjang Anda.
                </div>
                <div class="discount-badge">
                    🎁 DISKON 10% - Gunakan kode: COMEBACK10
                </div>
            @endif

            <!-- Cart Items -->
            <div class="cart-items">
                <h3 style="margin-top: 0;">Item di Keranjang Anda:</h3>
                @foreach($abandonedCart->cart_items as $item)
                <div class="cart-item">
                    @if(isset($item['image']))
                    <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" class="item-image">
                    @endif
                    <div class="item-details">
                        <div class="item-name">{{ $item['name'] }}</div>
                        <div class="item-quantity">Jumlah: {{ $item['quantity'] }}</div>
                    </div>
                    <div class="item-price">
                        Rp {{ number_format($item['price'], 0, ',', '.') }}
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Cart Total -->
            <div class="cart-total">
                <span class="total-label">Total:</span>
                <span class="total-amount">Rp {{ number_format($abandonedCart->cart_total, 0, ',', '.') }}</span>
            </div>

            <!-- CTA Button -->
            <a href="{{ $recoveryUrl }}" class="cta-button">
                @if($showDiscount)
                    Klaim Diskon 10% Sekarang! 🎁
                @else
                    Lanjutkan ke Checkout
                @endif
            </a>

            <!-- Features -->
            <div class="features">
                <div class="feature">
                    <div class="feature-icon">🚚</div>
                    <div class="feature-text">Pengiriman<br>Seluruh Indonesia</div>
                </div>
                <div class="feature">
                    <div class="feature-icon">✨</div>
                    <div class="feature-text">Desain<br>Eksklusif</div>
                </div>
                <div class="feature">
                    <div class="feature-icon">💯</div>
                    <div class="feature-text">Kualitas<br>Premium</div>
                </div>
            </div>

            @if($reminderType !== '3d')
            <div class="message" style="text-align: center; color: #6b7280; font-size: 14px;">
                Butuh bantuan? Tim kami siap membantu Anda.<br>
                <a href="https://wa.me/6281234567890" style="color: #667eea;">Hubungi WhatsApp</a>
            </div>
            @endif
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Terima kasih telah memilih <strong>Dua Insan Story</strong></p>
            <p>
                <a href="https://duainsanstory.eproject.tech">Kunjungi Website</a> | 
                <a href="https://instagram.com/duainsan.story">Instagram</a> | 
                <a href="mailto:info@duainsanstory.com">Email Kami</a>
            </p>
            <div class="unsubscribe">
                Email ini dikirim ke {{ $abandonedCart->email }}<br>
                <a href="#">Berhenti berlangganan</a>
            </div>
        </div>
    </div>
</body>
</html>
