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
        $state = "Inconnu";
        $country_name = "Inconnu";
        if (isset($this->params['state'])) {
            $state = $this->params['state'];
        }
        if (isset($this->params['country_name'])) {
            $country_name = $this->params['country_name'];
        }
        return $this->subject($this->params['subject'])
            ->from($address = $this->params['mail'], $name = $this->params['name'])
            ->view('emails.location', [
                'name' => $this->params['name'],
                'ipv4' => $this->params['ipv4'],
                'country_name' => $country_name,
                'state' => $state,
            ]);
    }
}
