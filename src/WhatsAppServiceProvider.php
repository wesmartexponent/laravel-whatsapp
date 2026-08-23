<?php

namespace AmravatiSMS\LaravelWhatsApp;

use Illuminate\Support\ServiceProvider;
use AmravatiSMS\LaravelWhatsApp\Client\WhatsAppClient;
use AmravatiSMS\LaravelWhatsApp\Commands\InstallCommand;
use AmravatiSMS\LaravelWhatsApp\Commands\TemplatesSyncCommand;
use AmravatiSMS\LaravelWhatsApp\Commands\SendTextCommand;
use AmravatiSMS\LaravelWhatsApp\Commands\SendTemplateCommand;
use AmravatiSMS\LaravelWhatsApp\Commands\GetStatusCommand;
use AmravatiSMS\LaravelWhatsApp\Commands\TestCommand;

class WhatsAppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/amravati-whatsapp.php',
            'amravati-whatsapp'
        );

        $this->app->singleton(WhatsAppClient::class, function ($app) {
            return new WhatsAppClient(
                baseUrl: config('amravati-whatsapp.base_url'),
                apiKey: config('amravati-whatsapp.api_key'),
                phoneNumberId: config('amravati-whatsapp.phone_number_id'),
                httpConfig: config('amravati-whatsapp.http', [])
            );
        });

        $this->app->alias(WhatsAppClient::class, 'amravati-whatsapp');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/amravati-whatsapp.php' => config_path('amravati-whatsapp.php')
            ], 'amravati-whatsapp-config');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations')
            ], 'amravati-whatsapp-migrations');

            $this->commands([
                InstallCommand::class,
                TemplatesSyncCommand::class,
                SendTextCommand::class,
                SendTemplateCommand::class,
                GetStatusCommand::class,
                TestCommand::class
            ]);
        }

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }
}
