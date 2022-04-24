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
        return $this->subject($this->params['subject'])
            ->from($address = $this->params['mail'], $name = $this->params['name'])
            ->view('emails.location', $this->params);
    }
}
