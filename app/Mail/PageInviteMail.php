<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PageInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public  $token, $email, $src, $id;
    /**
     * Create a new message instance.
     */
    public function __construct($token, $email, $src, $id)
    {
        $this->token = $token;
        $this->email = $email;
        $this->src = $src;
        $this->id = $id;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        return $this->from(['address' => env('MAIL_FROM_ADDRESS'), 'name' => 'Letivi'])
            ->subject('Letivi Page Invite Alert')
            ->view('mail.pageInvite');
    }
}
