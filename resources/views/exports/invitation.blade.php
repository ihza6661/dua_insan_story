<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $bride_name }} & {{ $groom_name }} - Undangan Pernikahan</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', 'Georgia', serif;
            font-size: 14px;
            line-height: 1.6;
            color: #3d2e28;
            background-color: #ffffff;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 30px;
        }
        
        /* Header Section */
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding: 30px 0;
            border-bottom: 2px solid #b89968;
        }
        
        .bismillah {
            font-size: 24px;
            color: #b89968;
            margin-bottom: 15px;
            font-style: italic;
        }
        
        .invitation-title {
            font-size: 16px;
            color: #796656;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 10px;
        }
        
        /* Opening Message */
        .opening-message {
            text-align: center;
            font-size: 14px;
            color: #3d2e28;
            margin-bottom: 40px;
            line-height: 1.8;
            font-style: italic;
            padding: 0 50px;
        }
        
        /* Couple Section */
        .couple-section {
            text-align: center;
            margin-bottom: 40px;
            padding: 30px 0;
        }
        
        .couple-names {
            font-size: 32px;
            font-weight: bold;
            color: #3d2e28;
            margin-bottom: 10px;
            font-family: 'Georgia', serif;
        }
        
        .couple-divider {
            font-size: 28px;
            color: #b89968;
            margin: 0 15px;
        }
        
        .bride-name, .groom-name {
            display: inline-block;
        }
        
        /* Event Details */
        .event-details {
            background-color: #faf9f8;
            border: 2px solid #b89968;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .event-title {
            font-size: 18px;
            font-weight: bold;
            color: #b89968;
            text-align: center;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .event-row {
            display: table;
            width: 100%;
            margin-bottom: 12px;
        }
        
        .event-label {
            display: table-cell;
            width: 30%;
            font-weight: bold;
            color: #796656;
            font-size: 13px;
        }
        
        .event-value {
            display: table-cell;
            width: 70%;
            color: #3d2e28;
            font-size: 13px;
        }
        
        /* Venue Section */
        .venue-section {
            background-color: #f5f0ea;
            padding: 25px;
            border-left: 4px solid #b89968;
            margin-bottom: 30px;
        }
        
        .venue-title {
            font-size: 16px;
            font-weight: bold;
            color: #3d2e28;
            margin-bottom: 10px;
        }
        
        .venue-address {
            font-size: 13px;
            color: #796656;
            margin-bottom: 8px;
            line-height: 1.6;
        }
        
        .venue-link {
            font-size: 12px;
            color: #b89968;
            font-style: italic;
        }
        
        /* Photo Section */
        .photo-section {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .hero-photo {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            border: 3px solid #b89968;
            margin-bottom: 20px;
        }
        
        /* Custom Fields */
        .custom-fields {
            margin-bottom: 30px;
        }
        
        .custom-field {
            margin-bottom: 15px;
            padding: 15px;
            background-color: #faf9f8;
            border-radius: 5px;
        }
        
        .custom-field-label {
            font-weight: bold;
            color: #796656;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
        
        .custom-field-value {
            color: #3d2e28;
            font-size: 13px;
            line-height: 1.5;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            padding-top: 30px;
            border-top: 2px solid #b89968;
            margin-top: 40px;
        }
        
        .footer-message {
            font-size: 14px;
            color: #796656;
            font-style: italic;
            margin-bottom: 15px;
        }
        
        .footer-brand {
            font-size: 16px;
            font-weight: bold;
            color: #b89968;
            margin-bottom: 5px;
        }
        
        .footer-tagline {
            font-size: 11px;
            color: #796656;
        }
        
        /* QR Code Section */
        .qr-section {
            text-align: center;
            margin-top: 20px;
            padding: 20px;
            background-color: #f5f0ea;
            border-radius: 8px;
        }
        
        .qr-text {
            font-size: 12px;
            color: #796656;
            margin-bottom: 10px;
        }
        
        .qr-url {
            font-size: 11px;
            color: #b89968;
            word-break: break-all;
        }
        
        /* Watermark */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px;
            color: rgba(184, 153, 104, 0.1);
            font-weight: bold;
            z-index: -1;
            pointer-events: none;
        }
        
        /* Print Styles */
        @media print {
            body {
                background-color: white;
            }
            
            .container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    @if($show_watermark ?? false)
    <div class="watermark">PREVIEW</div>
    @endif

    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="bismillah">بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ</div>
            <div class="invitation-title">Undangan Pernikahan</div>
        </div>

        <!-- Opening Message -->
        @if($opening_message)
        <div class="opening-message">
            {!! nl2br(e($opening_message)) !!}
        </div>
        @endif

        <!-- Couple Names -->
        <div class="couple-section">
            <div class="couple-names">
                <span class="bride-name">{{ $bride_name }}</span>
                <span class="couple-divider">&</span>
                <span class="groom-name">{{ $groom_name }}</span>
            </div>
        </div>

        <!-- Hero Photo -->
        @if($hero_photo)
        <div class="photo-section">
            <img src="{{ asset('storage/' . $hero_photo) }}" alt="Couple Photo" class="hero-photo">
        </div>
        @endif

        <!-- Event Details -->
        <div class="event-details">
            <div class="event-title">Detail Acara</div>
            
            @if($event_date)
            <div class="event-row">
                <div class="event-label">Tanggal:</div>
                <div class="event-value">{{ \Carbon\Carbon::parse($event_date)->format('l, d F Y') }}</div>
            </div>
            @endif
            
            @if($event_time)
            <div class="event-row">
                <div class="event-label">Waktu:</div>
                <div class="event-value">{{ $event_time }} WIB</div>
            </div>
            @endif
            
            @if($venue_name)
            <div class="event-row">
                <div class="event-label">Tempat:</div>
                <div class="event-value">{{ $venue_name }}</div>
            </div>
            @endif
        </div>

        <!-- Venue Address -->
        @if($venue_address)
        <div class="venue-section">
            <div class="venue-title">Lokasi Acara</div>
            <div class="venue-address">{{ $venue_address }}</div>
            @if($venue_maps_url)
            <div class="venue-link">Google Maps: {{ $venue_maps_url }}</div>
            @endif
        </div>
        @endif

        <!-- Custom Fields -->
        @if(!empty($custom_fields))
        <div class="custom-fields">
            @foreach($custom_fields as $fieldKey => $fieldValue)
                @if($fieldValue)
                <div class="custom-field">
                    <div class="custom-field-label">{{ ucfirst(str_replace('_', ' ', $fieldKey)) }}</div>
                    <div class="custom-field-value">
                        @if(is_array($fieldValue))
                            {{ implode(', ', $fieldValue) }}
                        @else
                            {!! nl2br(e($fieldValue)) !!}
                        @endif
                    </div>
                </div>
                @endif
            @endforeach
        </div>
        @endif

        <!-- QR Code Section -->
        @if($export_options['include_qr_code'] ?? true)
        <div class="qr-section">
            <div class="qr-text">Buka undangan digital di:</div>
            <div class="qr-url">{{ $invitation->public_url ?? config('app.url') . '/undangan/' . $invitation->slug }}</div>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <div class="footer-message">
                Merupakan suatu kehormatan dan kebahagiaan bagi kami<br>
                apabila Bapak/Ibu/Saudara/i berkenan hadir untuk memberikan do'a restu
            </div>
            <div class="footer-brand">Dua Insan Story</div>
            <div class="footer-tagline">Wujudkan Undangan Impian Anda</div>
        </div>
    </div>
</body>
</html>
