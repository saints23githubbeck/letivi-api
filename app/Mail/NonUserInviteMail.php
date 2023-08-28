<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NonUserInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public  $email;
    /**
     * Create a new message instance.
     */
    public function __construct($email)
    {
        $this->email = $email;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        return $this->from(['address' => env('MAIL_FROM_ADDRESS'), 'name' => 'Letivi'])
            ->subject('Letivi Invitation')
            ->view('mail.nonuser');
    }
}
