<?php

namespace AmravatiSMS\LaravelWhatsApp\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Request;

class WebhookReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Request $request,
        public array $payload,
    ) {}
}
