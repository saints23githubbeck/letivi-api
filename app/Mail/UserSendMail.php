<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserSendMail extends Mailable
{
    use Queueable, SerializesModels;

    public  $sender;
    public  $reciever;
    public  $body;
    /**
     * Create a new message instance.
     */
    public function __construct($sender, $reciever, $body)
    {
        $this->sender = $sender;
        $this->reciever = $reciever;
        $this->body = strip_tags(htmlspecialchars(trim($body)));
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        return $this->from(['address' => env('MAIL_FROM_ADDRESS'), 'name' => 'Letivi'])
            ->subject('Mail from user on Letivi')
            ->view('mail.sendmail');
    }
}
