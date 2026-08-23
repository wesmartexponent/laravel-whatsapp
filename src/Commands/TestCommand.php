<?php

namespace AmravatiSMS\LaravelWhatsApp\Commands;

use Illuminate\Console\Command;
use AmravatiSMS\LaravelWhatsApp\Facades\AmravatiSMS;

class TestCommand extends Command
{
    protected $signature = 'amravati:test {to?}';
    protected $description = 'Send a test message to verify configuration';

    public function handle(): int
    {
        $to = $this->argument('to');

        $this->info('Checking configuration...');

        if (! config('amravati-whatsapp.api_key')) {
            $this->error('AMRAVATISMS_API_KEY is not set in .env');
            return self::FAILURE;
        }

        if (! config('amravati-whatsapp.phone_number_id')) {
            $this->error('AMRAVATISMS_PHONE_NUMBER_ID is not set in .env');
            return self::FAILURE;
        }

        $this->info('API Key: ' . substr(config('amravati-whatsapp.api_key'), 0, 8) . '...');
        $this->info('Phone Number ID: ' . config('amravati-whatsapp.phone_number_id'));
        $this->info('Base URL: ' . config('amravati-whatsapp.base_url'));

        if (! $to) {
            $this->warn('No phone number provided. Skipping test send.');
            $this->warn('Run with: php artisan amravati:test +919876543210');
            return self::SUCCESS;
        }

        $this->info("Sending test message to {$to}...");

        $response = AmravatiSMS::sendText($to, 'Hello from AmravatiSMS Laravel WhatsApp! This is a test message.');

        if ($response->isSuccess()) {
            $this->info("Test message sent successfully! Message ID: {$response->messageId}");
            return self::SUCCESS;
        }

        $this->error("Test failed: {$response->getErrorMessage()}");
        return self::FAILURE;
    }
}
