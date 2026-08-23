<?php

namespace AmravatiSMS\LaravelWhatsApp\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'amravati:install';
    protected $description = 'Install the AmravatiSMS WhatsApp package';

    public function handle(): int
    {
        $this->info('Installing AmravatiSMS Laravel WhatsApp...');

        $this->call('vendor:publish', [
            '--tag' => 'amravati-whatsapp-config',
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'amravati-whatsapp-migrations',
        ]);

        $this->call('migrate');

        $this->newLine();
        $this->info('Add the following to your .env file:');
        $this->line('AMRAVATISMS_BASE_URL=https://automate.amravatisms.com');
        $this->line('AMRAVATISMS_API_KEY=your_api_key_here');
        $this->line('AMRAVATISMS_PHONE_NUMBER_ID=your_phone_number_id_here');

        $this->newLine();
        $this->info('Installation complete!');

        return self::SUCCESS;
    }
}
