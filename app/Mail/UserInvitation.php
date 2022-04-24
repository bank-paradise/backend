<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct($params)
    {
        $this->params = $params;
    }

    public function build()
    {
        return $this->subject("Nouvelle invitation")
            ->from($address = "noreply@bank-paradise.fr", $name = "Bank-Paradise")
            ->view('emails.invitation', $this->params);
    }
}
