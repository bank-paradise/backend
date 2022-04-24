<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserDelete extends Mailable
{
    use Queueable, SerializesModels;


    public function __construct($params)
    {
        $this->params = $params;
    }

    public function build()
    {
        return $this->subject("Compte supprimé")
            ->from($address = "support@bank-paradise.fr", $name = "Bank-Paradise")
            ->view('emails.deleteaccount', $this->params);
    }
}
