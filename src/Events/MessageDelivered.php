<?php

namespace AmravatiSMS\LaravelWhatsApp\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDelivered
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public array $webhookPayload,
    ) {}
}
