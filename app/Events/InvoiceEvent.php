<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $invoice;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($invoice)
    {
        $this->invoice = $invoice;
    }

    public function broadcastOn()
    {
        $channelName = 'invoice.' . $this->invoice['receiver']->rib;
        return [$channelName];
    }

    public function broadcastAs()
    {
        return 'invoice.received';
    }
}
