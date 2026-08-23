<?php

namespace AmravatiSMS\LaravelWhatsApp\Tests\Feature;

use AmravatiSMS\LaravelWhatsApp\Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use AmravatiSMS\LaravelWhatsApp\Events\WebhookReceived;
use AmravatiSMS\LaravelWhatsApp\Events\MessageDelivered;
use AmravatiSMS\LaravelWhatsApp\Events\MessageFailed;

class WebhookAdvancedTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('amravati-whatsapp.webhook.verify_signature', false);
        config()->set('amravati-whatsapp.logging.enabled', true);
    }

    /** @test */
    public function it_handles_message_read_webhook()
    {
        Event::fake([WebhookReceived::class]);

        $table = config('amravati-whatsapp.logging.table', 'whatsapp_message_logs');
        DB::table($table)->insert([
            'message_id' => 'wamid.read123',
            'phone_number' => '919876543210',
            'type' => 'text',
            'payload' => json_encode(['text' => 'Hello']),
            'status' => 'delivered',
            'success' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/webhook/whatsapp', [
            'message_id' => 'wamid.read123',
            'status' => 'read',
            'timestamp' => time(),
        ]);

        $response->assertOk();

        $this->assertDatabaseHas($table, [
            'message_id' => 'wamid.read123',
            'status' => 'read',
        ]);
    }

    /** @test */
    public function it_handles_failed_message_webhook()
    {
        Event::fake([MessageFailed::class]);

        $table = config('amravati-whatsapp.logging.table', 'whatsapp_message_logs');
        DB::table($table)->insert([
            'message_id' => 'wamid.fail456',
            'phone_number' => '919876543210',
            'type' => 'template',
            'payload' => json_encode(['template' => 'test']),
            'status' => 'sent',
            'success' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/webhook/whatsapp', [
            'message_id' => 'wamid.fail456',
            'status' => 'failed',
            'error' => ['message' => 'Recipient not found'],
        ]);

        $response->assertOk();
        Event::assertDispatched(MessageFailed::class);

        $this->assertDatabaseHas($table, [
            'message_id' => 'wamid.fail456',
            'status' => 'failed',
        ]);
    }

    /** @test */
    public function it_ignores_webhook_for_unknown_message()
    {
        $response = $this->postJson('/webhook/whatsapp', [
            'message_id' => 'wamid.unknown',
            'status' => 'delivered',
        ]);

        $response->assertOk();
    }

    /** @test */
    public function it_verifies_webhook_signature_with_correct_secret()
    {
        config()->set('amravati-whatsapp.webhook.verify_signature', true);
        config()->set('amravati-whatsapp.webhook.secret', 'my_super_secret');

        $payload = [
            'message_id' => 'wamid.secure789',
            'status' => 'delivered',
        ];

        $signature = hash_hmac('sha256', json_encode($payload), 'my_super_secret');

        $response = $this->withHeaders([
            'X-Webhook-Signature' => $signature,
            'Content-Type' => 'application/json',
        ])->postJson('/webhook/whatsapp', $payload);

        $response->assertOk();
    }

    /** @test */
    public function it_rejects_webhook_with_wrong_signature()
    {
        config()->set('amravati-whatsapp.webhook.verify_signature', true);
        config()->set('amravati-whatsapp.webhook.secret', 'my_super_secret');

        $response = $this->withHeaders([
            'X-Webhook-Signature' => 'wrong_signature',
        ])->postJson('/webhook/whatsapp', ['test' => 'data']);

        $response->assertUnauthorized();
    }

    /** @test */
    public function it_allows_webhook_when_verification_disabled()
    {
        config()->set('amravati-whatsapp.webhook.verify_signature', false);
        config()->set('amravati-whatsapp.webhook.secret', 'my_super_secret');

        $response = $this->postJson('/webhook/whatsapp', [
            'message_id' => 'wamid.open',
            'status' => 'delivered',
        ]);

        $response->assertOk();
    }
}
