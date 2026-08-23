<?php

namespace AmravatiSMS\LaravelWhatsApp\Tests\Feature;

use AmravatiSMS\LaravelWhatsApp\Tests\TestCase;
use AmravatiSMS\LaravelWhatsApp\Client\WhatsAppClient;
use AmravatiSMS\LaravelWhatsApp\Exceptions\WhatsAppException;
use Illuminate\Support\Facades\Http;

class WhatsAppClientTest extends TestCase
{
    protected WhatsAppClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = app(WhatsAppClient::class);
    }

    /** @test */
    public function it_sends_text_message()
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'message_id' => 'wamid.123456',
                'status' => 'queued',
            ], 200),
        ]);

        $response = $this->client->sendText('+919876543210', 'Hello World');

        $this->assertTrue($response->isSuccess());
        $this->assertEquals('wamid.123456', $response->messageId);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://automate.amravatisms.com/v2/whatsapp-business/messages'
                && $request['to'] === '919876543210'
                && $request['text'] === 'Hello World'
                && $request['type'] === 'text';
        });
    }

    /** @test */
    public function it_sends_image_by_url()
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'message_id' => 'wamid.789',
                'status' => 'queued',
            ], 200),
        ]);

        $response = $this->client->sendImageByUrl('+919876543210', 'https://example.com/image.jpg', 'Nice pic');

        $this->assertTrue($response->isSuccess());

        Http::assertSent(function ($request) {
            return $request['type'] === 'image'
                && $request['url'] === 'https://example.com/image.jpg'
                && $request['caption'] === 'Nice pic';
        });
    }

    /** @test */
    public function it_sends_document()
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'message_id' => 'wamid.doc123',
            ], 200),
        ]);

        $response = $this->client->sendDocument('+919876543210', 'https://example.com/invoice.pdf', 'invoice.pdf', 'Your invoice');

        $this->assertTrue($response->isSuccess());

        Http::assertSent(function ($request) {
            return $request['type'] === 'document'
                && $request['filename'] === 'invoice.pdf'
                && $request['caption'] === 'Your invoice';
        });
    }

    /** @test */
    public function it_sends_template_with_body_params()
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'message_id' => 'wamid.tpl456',
                'status' => 'queued',
            ], 200),
        ]);

        $response = $this->client->template('order_status')
            ->to('+919876543210')
            ->bodyParams(['John', 'Shipped', 'ORD-1234'])
            ->send();

        $this->assertTrue($response->isSuccess());

        Http::assertSent(function ($request) {
            return $request['type'] === 'template'
                && $request['name'] === 'order_status'
                && $request['bodyParams'] === ['John', 'Shipped', 'ORD-1234'];
        });
    }

    /** @test */
    public function it_sends_template_with_header_image()
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'message_id' => 'wamid.tpl789',
            ], 200),
        ]);

        $response = $this->client->template('promo')
            ->to('+919876543210')
            ->headerImage('https://example.com/promo.jpg')
            ->bodyParams(['John', '50% OFF'])
            ->send();

        $this->assertTrue($response->isSuccess());

        Http::assertSent(function ($request) {
            return $request['headerParams'] === [[
                'type' => 'image',
                'url' => 'https://example.com/promo.jpg',
            ]];
        });
    }

    /** @test */
    public function it_gets_message_status()
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'message_id' => 'wamid.123',
                'status' => 'delivered',
            ], 200),
        ]);

        $response = $this->client->getStatus('wamid.123');

        $this->assertTrue($response->isSuccess());
        $this->assertEquals('delivered', $response->status);
    }

    /** @test */
    public function it_gets_templates_with_pagination()
    {
        Http::fake([
            '*' => Http::response([
                'data' => [
                    ['name' => 'hello_world', 'language' => 'en_US', 'status' => 'APPROVED'],
                    ['name' => 'order_status', 'language' => 'en_US', 'status' => 'APPROVED'],
                ],
                'paging' => [
                    'next_cursor' => null,
                ],
            ], 200),
        ]);

        $templates = $this->client->getTemplates(limit: 100);

        $this->assertCount(2, $templates['data']);
        $this->assertEquals('hello_world', $templates['data'][0]['name']);
    }

    /** @test */
    public function it_throws_exception_on_api_error()
    {
        Http::fake([
            '*' => Http::response([
                'message' => 'Invalid API key',
                'error' => ['code' => 401],
            ], 401),
        ]);

        $this->expectException(WhatsAppException::class);
        $this->expectExceptionMessage('Invalid API key');

        $this->client->sendText('+919876543210', 'Test');
    }

    /** @test */
    public function it_includes_authorization_header()
    {
        Http::fake([
            '*' => Http::response(['success' => true, 'message_id' => 'wamid.1'], 200),
        ]);

        $this->client->sendText('+919876543210', 'Test');

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer test_api_key_12345');
        });
    }

    /** @test */
    public function it_uses_different_phone_number_id()
    {
        Http::fake([
            '*' => Http::response(['success' => true, 'message_id' => 'wamid.1'], 200),
        ]);

        $this->client->withPhoneNumberId('9999999999')
            ->sendText('+919876543210', 'Test');

        Http::assertSent(function ($request) {
            return $request['phoneNoId'] === '9999999999';
        });
    }
}
