<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProfileInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public  $user, $email;
    /**
     * Create a new message instance.
     */
    public function __construct($user, $email)
    {
        $this->user = $user;
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
            ->subject('Letivi Profile Invite')
            ->view('mail.profileInvite');
    }
}
