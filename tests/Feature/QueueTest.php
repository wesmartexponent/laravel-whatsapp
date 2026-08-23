<?php

namespace AmravatiSMS\LaravelWhatsApp\Tests\Feature;

use AmravatiSMS\LaravelWhatsApp\Tests\TestCase;
use AmravatiSMS\LaravelWhatsApp\Client\WhatsAppClient;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Http;

class QueueTest extends TestCase
{
    /** @test */
    public function it_returns_queued_response_when_queue_mode_enabled()
    {
        config()->set('amravati-whatsapp.queue.enabled', true);
        config()->set('amravati-whatsapp.queue.connection', 'sync');

        $client = app(WhatsAppClient::class);
        $response = $client->queue()->sendText('+919876543210', 'Queued message');

        $this->assertTrue($response->isSuccess());
        $this->assertTrue($response->isQueued());
        $this->assertNull($response->messageId);
    }

    /** @test */
    public function it_sends_synchronously_when_queue_disabled()
    {
        config()->set('amravati-whatsapp.queue.enabled', false);

        Http::fake([
            '*' => Http::response(['success' => true, 'message_id' => 'wamid.1'], 200),
        ]);

        $client = app(WhatsAppClient::class);
        $response = $client->queue()->sendText('+919876543210', 'Test');

        $this->assertTrue($response->isSuccess());
        $this->assertFalse($response->isQueued());
    }
}
