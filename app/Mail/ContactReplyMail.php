<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public $contact;
    public $replyMessage;

    public function __construct($contact, string $replyMessage)
    {
        $this->contact = $contact;
        $this->replyMessage = $replyMessage;
    }

    public function build()
    {
        $subject = !empty($this->contact->subject) ? $this->contact->subject : 'General Inquiry';
        return $this->subject("Re: {$subject} - Asteghi Support")
                    ->view('emails.contact_reply');
    }
}