<?php

namespace AmravatiSMS\LaravelWhatsApp\Commands;

use Illuminate\Console\Command;
use AmravatiSMS\LaravelWhatsApp\Facades\AmravatiSMS;

class SendTextCommand extends Command
{
    protected $signature = 'amravati:send:text {to} {message}';
    protected $description = 'Send a text message via WhatsApp';

    public function handle(): int
    {
        $to = $this->argument('to');
        $message = $this->argument('message');

        $this->info("Sending text message to {$to}...");

        $response = AmravatiSMS::sendText($to, $message);

        if ($response->isSuccess()) {
            $this->info("Message sent! ID: {$response->messageId}");
            return self::SUCCESS;
        }

        $this->error("Failed: {$response->getErrorMessage()}");
        return self::FAILURE;
    }
}
