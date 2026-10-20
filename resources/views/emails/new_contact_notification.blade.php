<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New Contact Inquiry</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 30px;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border: 1px solid #e2e8f0;">
        <div style="background: linear-gradient(135deg, #1e293b, #0f172a); padding: 25px 30px; text-align: center;">
            <h1 style="color: #ffffff; margin: 0; font-size: 22px; font-weight: 700;">Asteghi Support Inbox</h1>
            <p style="color: #94a3b8; margin: 5px 0 0 0; font-size: 13px;">New Student / Visitor Inquiry Received</p>
        </div>

        <div style="padding: 30px;">
            <div style="background: #f1f5f9; padding: 16px 20px; border-radius: 8px; margin-bottom: 20px;">
                <p style="margin: 0 0 8px 0; font-size: 14px; color: #334155;"><strong>Sender:</strong> {{ $contact->name }}</p>
                <p style="margin: 0 0 8px 0; font-size: 14px; color: #334155;"><strong>Email:</strong> <a href="mailto:{{ $contact->email }}" style="color: #2563eb;">{{ $contact->email }}</a></p>
                <p style="margin: 0; font-size: 14px; color: #334155;"><strong>Subject:</strong> {{ $contact->subject ?? 'General Inquiry' }}</p>
            </div>

            <p style="font-size: 13px; font-weight: 600; color: #64748b; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Message:</p>
            <div style="background: #ffffff; border-left: 4px solid #3b82f6; border: 1px solid #e2e8f0; border-left-width: 4px; padding: 18px; border-radius: 6px; font-size: 15px; color: #1e293b; line-height: 1.6; white-space: pre-line;">
{{ $contact->message }}
            </div>

            <div style="text-align: center; margin-top: 30px;">
                <a href="https://asteghi.com/support/contacts" style="display: inline-block; background: #2563eb; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; font-size: 14px;">Open Support Inbox & Reply</a>
            </div>

            <p style="font-size: 12px; color: #94a3b8; margin-top: 30px; text-align: center;">
                You can also reply directly to this notification email to reach {{ $contact->name }}.
            </p>
        </div>
    </div>
</body>
</html>
