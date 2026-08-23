<?php

use Illuminate\Support\Facades\Route;
use AmravatiSMS\LaravelWhatsApp\Http\Controllers\WebhookController;
use AmravatiSMS\LaravelWhatsApp\Http\Middleware\VerifyWebhookSignature;

if (config('amravati-whatsapp.webhook.enabled', true)) {
    Route::post(
        config('amravati-whatsapp.webhook.route', 'webhook/whatsapp'),
        [WebhookController::class, 'handle']
    )->middleware(VerifyWebhookSignature::class)
     ->name('amravati.whatsapp.webhook');
}
