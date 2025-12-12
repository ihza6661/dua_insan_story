<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Digital Invitation is Ready</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Jost:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Jost', Arial, sans-serif;
            line-height: 1.6;
            color: hsl(20, 20%, 25%);
            background-color: hsl(40, 30%, 97%);
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .email-wrapper {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }
        .brand-name {
            font-style: italic;
            font-size: 24px;
            font-weight: 600;
            letter-spacing: 2px;
            margin-bottom: 15px;
            color: hsl(40, 30%, 97%);
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.95;
            font-weight: 300;
        }
        .content {
            background-color: hsl(40, 30%, 97%);
            padding: 35px 30px;
        }
        .invitation-card {
            background-color: white;
            padding: 25px;
            border-left: 4px solid #10B981;
            margin: 25px 0;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }
        .invitation-card h3 {
            margin-top: 0;
            color: hsl(20, 25%, 22%);
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        .invitation-url {
            background-color: hsl(40, 30%, 94%);
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            word-break: break-all;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            color: #059669;
            font-weight: 500;
        }
        .button {
            display: inline-block;
            background: #10B981;
            color: white;
            padding: 14px 35px;
            text-decoration: none;
            border-radius: 6px;
            margin: 10px 5px;
            font-weight: 500;
            letter-spacing: 0.5px;
            transition: background 0.3s ease;
        }
        .button:hover {
            background: #059669;
        }
        .button-secondary {
            background: hsl(20, 25%, 22%);
        }
        .button-secondary:hover {
            background: hsl(20, 25%, 28%);
        }
        .info-box {
            background-color: hsl(142, 60%, 95%);
            border-left: 4px solid #10B981;
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .tips-box {
            background-color: hsl(204, 86%, 95%);
            border-left: 4px solid hsl(204, 86%, 53%);
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .footer {
            text-align: center;
            padding: 25px 20px;
            background-color: hsl(40, 25%, 88%);
            font-size: 12px;
            color: hsl(20, 15%, 55%);
        }
        .footer-brand {
            font-style: italic;
            font-size: 18px;
            font-weight: 600;
            letter-spacing: 1.5px;
            color: hsl(20, 25%, 22%);
            margin-top: 10px;
        }
        table {
            width: 100%;
        }
        .text-right {
            text-align: right;
        }
        .celebration-emoji {
            font-size: 48px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="header">
            <div class="brand-name">DuaInsan.Story</div>
            <div class="celebration-emoji">🎉</div>
            <h1>Your Invitation is Ready!</h1>
            <p>Your digital invitation is now live and ready to share</p>
        </div>
        
        <div class="content">
            <p>Hi {{ $customer->full_name }},</p>
            
            <p>Congratulations! Your digital wedding invitation has been created and is now <strong>active and ready to share</strong>.</p>
        
            <div class="invitation-card">
                <h3>📱 Your Invitation Details</h3>
                <table>
                    <tr>
                        <td><strong>Template:</strong></td>
                        <td class="text-right">{{ $template->name }}</td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td class="text-right"><span style="color: #10B981;">● Active</span></td>
                    </tr>
                    <tr>
                        <td><strong>Activated:</strong></td>
                        <td class="text-right">{{ $invitation->activated_at->format('F d, Y') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Valid Until:</strong></td>
                        <td class="text-right">{{ $expiresAt->format('F d, Y') }} <small>(12 months)</small></td>
                    </tr>
                </table>
            </div>

            <div class="info-box">
                <strong>🔗 Your Public Invitation Link</strong>
                <div class="invitation-url">
                    {{ $publicUrl }}
                </div>
                <p style="margin: 10px 0 0 0; font-size: 14px;">
                    Share this link with your guests via WhatsApp, social media, or any messaging platform. 
                    Your invitation is <strong>live right now</strong> and ready to be viewed!
                </p>
            </div>

            <center>
                <a href="{{ $publicUrl }}" class="button">
                    🔍 View Your Invitation
                </a>
                <a href="{{ $editUrl }}" class="button button-secondary">
                    ✏️ Customize Details
                </a>
            </center>

            <div class="tips-box">
                <strong>💡 Quick Tips</strong>
                <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                    <li><strong>Customize anytime:</strong> You can edit your invitation details (names, dates, venue) at any time. Changes are live immediately!</li>
                    <li><strong>Share easily:</strong> Copy the link above and share it directly on WhatsApp, Instagram, or any platform you prefer.</li>
                    <li><strong>Track views:</strong> Check how many people have viewed your invitation from your dashboard.</li>
                    <li><strong>No activation needed:</strong> Your invitation is already active and publicly accessible.</li>
                    <li><strong>Expires in 12 months:</strong> Your invitation will remain active for a full year from today.</li>
                </ul>
            </div>

            <div class="info-box">
                <strong>📝 What's next?</strong>
                <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                    <li>Click "Customize Details" above to add your wedding information (bride/groom names, date, venue, etc.)</li>
                    <li>Preview your invitation to see how it looks to your guests</li>
                    <li>Once you're happy, start sharing your link with family and friends!</li>
                    <li>Monitor invitation views from your "My Invitations" dashboard</li>
                </ul>
            </div>
            
            <p style="margin-top: 30px;">Need help? Reply to this email or contact our support team. We're here to ensure your invitation is perfect!</p>
            
            <p style="margin-top: 30px;">Best regards,<br>
            <strong>Dua Insan Story Team</strong></p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} Dua Insan Story. All rights reserved.</p>
            <p style="margin-top: 5px;">This email was sent because you purchased a digital invitation template.</p>
            <div class="footer-brand">DuaInsan.Story</div>
        </div>
    </div>
</body>
</html>
