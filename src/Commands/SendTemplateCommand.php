<?php

namespace AmravatiSMS\LaravelWhatsApp\Commands;

use Illuminate\Console\Command;
use AmravatiSMS\LaravelWhatsApp\Facades\AmravatiSMS;

class SendTemplateCommand extends Command
{
    protected $signature = 'amravati:send:template {to} {template} {--params=} {--language=en_US}';
    protected $description = 'Send a template message via WhatsApp';

    public function handle(): int
    {
        $to = $this->argument('to');
        $template = $this->argument('template');
        $params = $this->option('params') ? explode(',', $this->option('params')) : [];
        $language = $this->option('language');

        $this->info("Sending template '{$template}' to {$to}...");

        $response = AmravatiSMS::template($template)
            ->to($to)
            ->language($language)
            ->bodyParams($params)
            ->send();

        if ($response->isSuccess()) {
            $this->info("Template sent! ID: {$response->messageId}");
            return self::SUCCESS;
        }

        $this->error("Failed: {$response->getErrorMessage()}");
        return self::FAILURE;
    }
}
