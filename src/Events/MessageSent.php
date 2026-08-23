<?php

namespace AmravatiSMS\LaravelWhatsApp\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use AmravatiSMS\LaravelWhatsApp\Responses\MessageResponse;

class MessageSent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public array $payload,
        public MessageResponse $response,
    ) {}
}
