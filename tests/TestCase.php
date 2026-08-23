<?php

namespace AmravatiSMS\LaravelWhatsApp\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use AmravatiSMS\LaravelWhatsApp\WhatsAppServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            WhatsAppServiceProvider::class
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('amravati-whatsapp.api_key', 'test_api_key_12345');
        $app['config']->set('amravati-whatsapp.phone_number_id', '1234567890');
        $app['config']->set('amravati-whatsapp.base_url', 'https://automate.amravatisms.com');
        $app['config']->set('amravati-whatsapp.logging.enabled', false);
        $app['config']->set('amravati-whatsapp.queue.enabled', false);
    }
}
