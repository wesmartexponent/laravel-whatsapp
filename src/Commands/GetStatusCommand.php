<?php

namespace AmravatiSMS\LaravelWhatsApp\Commands;

use Illuminate\Console\Command;
use AmravatiSMS\LaravelWhatsApp\Facades\AmravatiSMS;

class GetStatusCommand extends Command
{
    protected $signature = 'amravati:status {messageId}';
    protected $description = 'Get the status of a WhatsApp message';

    public function handle(): int
    {
        $messageId = $this->argument('messageId');

        $this->info("Fetching status for {$messageId}...");

        $response = AmravatiSMS::getStatus($messageId);

        $this->table(
            ['Field', 'Value'],
            [
                ['Message ID', $response->messageId ?? 'N/A'],
                ['Status', $response->status ?? 'N/A'],
                ['Success', $response->success ? 'Yes' : 'No'],
            ]
        );

        return self::SUCCESS;
    }
}
