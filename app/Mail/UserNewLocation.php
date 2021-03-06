<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserNewLocation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct($params)
    {
        $this->params = $params;
    }

    public function build()
    {
        return $this->subject("Nouvelle localisation détectée")
            ->from($address = "support@bank-paradise.fr", $name = "Bank-Paradise")
            ->view('emails.location', $this->params);
    }
}
