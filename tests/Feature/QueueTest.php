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

        Queue::fake();
        Http::fake();

        $client = app(WhatsAppClient::class);
        $response = $client->queue()->sendText('+919876543210', 'Queued message');

        $this->assertTrue($response->isSuccess());
        $this->assertTrue($response->isQueued());
        $this->assertNull($response->messageId);

        // The send must be deferred to the queue, not performed inline.
        Http::assertNothingSent();
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

    /** @test */
    public function queue_mode_does_not_leak_into_the_shared_instance()
    {
        config()->set('amravati-whatsapp.queue.enabled', true);
        config()->set('amravati-whatsapp.queue.connection', 'sync');

        Queue::fake();
        Http::fake([
            '*' => Http::response(['success' => true, 'message_id' => 'wamid.1'], 200),
        ]);

        $client = app(WhatsAppClient::class);

        // One opt-in queued send...
        $client->queue()->sendText('+919876543210', 'Queued');

        // ...must not make the next send on the same instance queued too.
        $response = $client->sendText('+919876543210', 'Immediate');

        $this->assertFalse($response->isQueued());
        $this->assertEquals('wamid.1', $response->messageId);
    }

    /** @test */
    public function phone_number_id_override_does_not_leak_into_the_shared_instance()
    {
        Http::fake([
            '*' => Http::response(['success' => true, 'message_id' => 'wamid.1'], 200),
        ]);

        $client = app(WhatsAppClient::class);

        $client->withPhoneNumberId('9999999999')->sendText('+919876543210', 'Override');
        $client->sendText('+919876543210', 'Default');

        Http::assertSent(fn ($request) => $request['phoneNoId'] === '9999999999');
        Http::assertSent(fn ($request) => $request['phoneNoId'] === '1234567890');
    }
}
