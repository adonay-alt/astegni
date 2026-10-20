<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewContactNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $contact;

    public function __construct($contact)
    {
        $this->contact = $contact;
    }

    public function build()
    {
        $subject = !empty($this->contact->subject) ? $this->contact->subject : 'General Inquiry';

        return $this->subject("New Inquiry: {$subject} (from {$this->contact->name})")
                    ->replyTo($this->contact->email, $this->contact->name)
                    ->view('emails.new_contact_notification');
    }
}
