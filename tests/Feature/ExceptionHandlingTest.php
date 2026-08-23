<?php

namespace AmravatiSMS\LaravelWhatsApp\Tests\Feature;

use AmravatiSMS\LaravelWhatsApp\Tests\TestCase;
use AmravatiSMS\LaravelWhatsApp\Client\WhatsAppClient;
use AmravatiSMS\LaravelWhatsApp\Exceptions\WhatsAppException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Event;
use AmravatiSMS\LaravelWhatsApp\Events\MessageFailed;

class ExceptionHandlingTest extends TestCase
{
    /** @test */
    public function it_throws_whatsapp_exception_on_401_unauthorized()
    {
        Http::fake([
            '*' => Http::response([
                'message' => 'Invalid API key provided',
                'error' => ['code' => 401, 'type' => 'auth_error'],
            ], 401),
        ]);

        $client = app(WhatsAppClient::class);

        try {
            $client->sendText('+919876543210', 'Test');
            $this->fail('Expected WhatsAppException was not thrown');
        } catch (WhatsAppException $e) {
            $this->assertEquals('Invalid API key provided', $e->getMessage());
            $this->assertEquals(401, $e->getStatusCode());
            $this->assertNotNull($e->getResponse());
            $this->assertEquals('auth_error', $e->getResponse()['error']['type']);
        }
    }

    /** @test */
    public function it_throws_whatsapp_exception_on_429_rate_limit()
    {
        Http::fake([
            '*' => Http::response([
                'message' => 'Rate limit exceeded. Try again in 60 seconds.',
                'error' => ['code' => 429, 'retry_after' => 60],
            ], 429),
        ]);

        $this->expectException(WhatsAppException::class);
        $this->expectExceptionMessage('Rate limit exceeded');

        $client = app(WhatsAppClient::class);
        $client->sendText('+919876543210', 'Test');
    }

    /** @test */
    public function it_throws_whatsapp_exception_on_500_server_error()
    {
        Http::fake([
            '*' => Http::response([
                'message' => 'Internal server error',
            ], 500),
        ]);

        $this->expectException(WhatsAppException::class);
        $this->expectExceptionMessage('Internal server error');

        $client = app(WhatsAppClient::class);
        $client->sendText('+919876543210', 'Test');
    }

    /** @test */
    public function it_fires_message_failed_event_on_exception()
    {
        Event::fake([MessageFailed::class]);

        Http::fake([
            '*' => Http::response(['message' => 'Error'], 500),
        ]);

        try {
            $client = app(WhatsAppClient::class);
            $client->sendText('+919876543210', 'Test');
        } catch (WhatsAppException $e) {
            // Expected
        }

        Event::assertDispatched(MessageFailed::class, function ($event) {
            return $event->payload['to'] === '919876543210'
                && $event->payload['text'] === 'Test';
        });
    }

    /** @test */
    public function it_normalizes_various_phone_formats()
    {
        Http::fake([
            '*' => Http::response(['success' => true, 'message_id' => 'wamid.1'], 200),
        ]);

        $client = app(WhatsAppClient::class);
        $formats = [
            '+91 98765 43210',
            '+91-98765-43210',
            '91 98765 43210',
            '9876543210',
            '+919876543210',
        ];

        foreach ($formats as $phone) {
            $client->sendText($phone, 'Test');
        }

        Http::assertSentCount(5);

        Http::assertSent(function ($request) {
            return $request['to'] === '919876543210';
        });
    }

    /** @test */
    public function it_includes_correct_headers_in_request()
    {
        Http::fake([
            '*' => Http::response(['success' => true, 'message_id' => 'wamid.1'], 200),
        ]);

        $client = app(WhatsAppClient::class);
        $client->sendText('+919876543210', 'Test');

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer test_api_key_12345')
                && $request->hasHeader('Content-Type', 'application/json')
                && $request->hasHeader('Accept', 'application/json');
        });
    }
}
