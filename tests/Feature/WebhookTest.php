<?php

namespace AmravatiSMS\LaravelWhatsApp\Tests\Feature;

use AmravatiSMS\LaravelWhatsApp\Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use AmravatiSMS\LaravelWhatsApp\Events\WebhookReceived;
use AmravatiSMS\LaravelWhatsApp\Events\MessageDelivered;
use AmravatiSMS\LaravelWhatsApp\Events\MessageFailed;

class WebhookTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('amravati-whatsapp.webhook.verify_signature', false);
        config()->set('amravati-whatsapp.logging.enabled', true);
    }

    /** @test */
    public function it_receives_webhook_and_fires_events()
    {
        Event::fake([WebhookReceived::class, MessageDelivered::class]);

        $response = $this->postJson('/webhook/whatsapp', [
            'message_id' => 'wamid.123',
            'status' => 'delivered',
            'timestamp' => time(),
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        Event::assertDispatched(WebhookReceived::class);
        Event::assertDispatched(MessageDelivered::class);
    }

    /** @test */
    public function it_updates_message_log_on_delivery_webhook()
    {
        $table = config('amravati-whatsapp.logging.table', 'whatsapp_message_logs');
        DB::table($table)->insert([
            'message_id' => 'wamid.123',
            'phone_number' => '919876543210',
            'type' => 'text',
            'payload' => json_encode(['text' => 'Hello']),
            'status' => 'sent',
            'success' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson('/webhook/whatsapp', [
            'message_id' => 'wamid.123',
            'status' => 'delivered',
        ]);

        $this->assertDatabaseHas($table, [
            'message_id' => 'wamid.123',
            'status' => 'delivered',
        ]);
    }

    /** @test */
    public function it_rejects_invalid_signature()
    {
        config()->set('amravati-whatsapp.webhook.verify_signature', true);
        config()->set('amravati-whatsapp.webhook.secret', 'mysecret');

        $response = $this->postJson('/webhook/whatsapp', ['test' => 'data']);

        $response->assertUnauthorized()
            ->assertJson(['error' => 'Missing signature']);
    }

    /** @test */
    public function it_accepts_valid_signature()
    {
        config()->set('amravati-whatsapp.webhook.verify_signature', true);
        config()->set('amravati-whatsapp.webhook.secret', 'mysecret');

        $payload = json_encode(['message_id' => 'wamid.123', 'status' => 'delivered']);
        $signature = hash_hmac('sha256', $payload, 'mysecret');

        $response = $this->withHeaders([
            'X-Webhook-Signature' => $signature,
        ])->postJson('/webhook/whatsapp', json_decode($payload, true));

        $response->assertOk();
    }
}
