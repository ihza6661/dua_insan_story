<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheduled Activation Reminder</title>
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
            background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);
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
            border-left: 4px solid #3B82F6;
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
        .button {
            display: inline-block;
            background: #3B82F6;
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
            background: #2563EB;
        }
        .button-secondary {
            background: hsl(20, 25%, 22%);
        }
        .button-secondary:hover {
            background: hsl(20, 25%, 28%);
        }
        .info-box {
            background-color: hsl(217, 91%, 95%);
            border-left: 4px solid #3B82F6;
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .warning-box {
            background-color: hsl(45, 100%, 95%);
            border-left: 4px solid hsl(45, 100%, 51%);
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
        .clock-emoji {
            font-size: 48px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="header">
            <div class="brand-name">DuaInsan.Story</div>
            <div class="clock-emoji">⏰</div>
            <h1>Activation Reminder</h1>
            <p>Your invitation will be activated tomorrow</p>
        </div>
        
        <div class="content">
            <p>Hi {{ $customer->full_name }},</p>
            
            <p>This is a friendly reminder that your digital wedding invitation is scheduled to be <strong>automatically activated tomorrow</strong>!</p>
        
            <div class="invitation-card">
                <h3>📅 Scheduled Activation Details</h3>
                <table>
                    <tr>
                        <td><strong>Template:</strong></td>
                        <td class="text-right">{{ $template->name }}</td>
                    </tr>
                    <tr>
                        <td><strong>Current Status:</strong></td>
                        <td class="text-right"><span style="color: #F59E0B;">● Draft (Scheduled)</span></td>
                    </tr>
                    <tr>
                        <td><strong>Scheduled For:</strong></td>
                        <td class="text-right"><strong>{{ $scheduledAt->format('F d, Y \a\t H:i') }}</strong></td>
                    </tr>
                    <tr>
                        <td><strong>Time Until Activation:</strong></td>
                        <td class="text-right">~{{ $scheduledAt->diffForHumans() }}</td>
                    </tr>
                </table>
            </div>

            <div class="info-box">
                <strong>ℹ️ What happens at activation?</strong>
                <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                    <li>Your invitation will become <strong>publicly accessible</strong> via your unique link</li>
                    <li>Status will change from "Draft" to "Active"</li>
                    <li>You'll receive another email confirmation when it's live</li>
                    <li>The invitation will be valid for 12 months from activation</li>
                </ul>
            </div>

            <div class="warning-box">
                <strong>⚠️ Last chance to make changes!</strong>
                <p style="margin: 10px 0 0 0;">
                    If you need to make any edits to your invitation details (names, dates, venue, photos), 
                    now is the perfect time! You can still edit after activation, but it's best to finalize 
                    everything before it goes live.
                </p>
            </div>

            <center>
                <a href="{{ $previewUrl }}" class="button">
                    👁️ Preview Invitation
                </a>
                <a href="{{ $editUrl }}" class="button button-secondary">
                    ✏️ Edit Details
                </a>
            </center>

            <div class="info-box">
                <strong>💡 Quick Actions</strong>
                <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                    <li><strong>Cancel/Reschedule:</strong> You can cancel the scheduled activation or change the date from your dashboard</li>
                    <li><strong>Manual Activation:</strong> Want it live now? You can activate it immediately from your invitations page</li>
                    <li><strong>Final Review:</strong> Click "Preview Invitation" above to see exactly how it will look to your guests</li>
                </ul>
            </div>

            <div class="info-box" style="background-color: hsl(142, 60%, 95%); border-left-color: #10B981;">
                <strong>✅ Checklist Before Activation</strong>
                <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                    <li>Bride and Groom names are spelled correctly</li>
                    <li>Event date and time are accurate</li>
                    <li>Venue address and maps link work properly</li>
                    <li>Photos are uploaded and look good</li>
                    <li>Any additional information is complete</li>
                </ul>
            </div>
            
            <p style="margin-top: 30px;">
                <strong>Need to make changes?</strong> You have until {{ $scheduledAt->format('F d, Y \a\t H:i') }} to update your invitation details. After activation, your invitation will be live and publicly accessible.
            </p>
            
            <p style="margin-top: 20px;">Questions or need help? Reply to this email and we'll be happy to assist!</p>
            
            <p style="margin-top: 30px;">Best regards,<br>
            <strong>Dua Insan Story Team</strong></p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} Dua Insan Story. All rights reserved.</p>
            <p style="margin-top: 5px;">This reminder was sent because you scheduled your invitation for automatic activation.</p>
            <div class="footer-brand">DuaInsan.Story</div>
        </div>
    </div>
</body>
</html>
