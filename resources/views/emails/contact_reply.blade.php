<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Support Response</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 30px;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border: 1px solid #e2e8f0;">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #1e293b, #0f172a); padding: 30px; text-align: center;">
            <h1 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 700;">Asteghi Support</h1>
            <p style="color: #94a3b8; margin: 5px 0 0 0; font-size: 14px;">Official Support Response</p>
        </div>

        <!-- Body -->
        <div style="padding: 30px;">
            <p style="font-size: 16px; color: #1e293b; margin-top: 0;">Hello <strong>{{ $contact->name }}</strong>,</p>
            
            <p style="font-size: 15px; color: #334155; line-height: 1.6;">
                Thank you for contacting Asteghi Support regarding <em>"{{ $contact->subject ?? 'your inquiry' }}"</em>. Here is our official response to your inquiry:
            </p>

            <!-- Support Message Box -->
            <div style="background: #f1f5f9; border-left: 4px solid #3b82f6; padding: 20px; border-radius: 4px; margin: 25px 0; font-size: 15px; color: #1e293b; line-height: 1.6; white-space: pre-line;">
{{ $replyMessage }}
            </div>

            <!-- Original Message Quote -->
            <div style="border-top: 1px dashed #cbd5e1; padding-top: 20px; margin-top: 30px;">
                <p style="font-size: 13px; color: #64748b; margin-bottom: 8px;"><strong>Your Original Message (Sent on {{ \Carbon\Carbon::parse($contact->created_at)->toFormattedDateString() }}):</strong></p>
                <p style="font-size: 13px; color: #64748b; font-style: italic; background: #f8fafc; padding: 12px; border-radius: 6px; margin: 0;">
                    "{{ $contact->message }}"
                </p>
            </div>

            <p style="font-size: 14px; color: #64748b; margin-top: 30px;">
                If you have any further questions, feel free to reply directly to this email or visit our Help Center at <a href="https://asteghi.com" style="color: #3b82f6; text-decoration: none;">asteghi.com</a>.
            </p>

            <p style="font-size: 14px; color: #1e293b; font-weight: 600; margin-bottom: 0;">
                Best regards,<br>
                The Asteghi Support Team
            </p>
        </div>
    </div>
</body>
</html>