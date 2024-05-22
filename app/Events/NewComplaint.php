<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewComplaint implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $merchant_id;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($merchant_id)
    {
        $this->merchant_id = $merchant_id;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('new-complaint'.$this->merchant_id);
    }

    public function broadcastAs()
    {
        return 'new-complaint-sent';
    }
}
