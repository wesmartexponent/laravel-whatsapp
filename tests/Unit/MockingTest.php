<?php

namespace AmravatiSMS\LaravelWhatsApp\Tests\Unit;

use AmravatiSMS\LaravelWhatsApp\Tests\TestCase;
use AmravatiSMS\LaravelWhatsApp\Client\WhatsAppClient;
use Illuminate\Support\Facades\Http;

class MockingTest extends TestCase
{
    /** @test */
    public function it_can_mock_text_message_response()
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'message_id' => 'wamid.text123',
                'status' => 'queued',
            ], 200),
        ]);

        $client = app(WhatsAppClient::class);
        $response = $client->sendText('+919876543210', 'Hello');

        $this->assertTrue($response->isSuccess());
        $this->assertEquals('wamid.text123', $response->messageId);
    }

    /** @test */
    public function it_can_mock_template_message_response()
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'message_id' => 'wamid.tpl456',
                'status' => 'queued',
            ], 200),
        ]);

        $client = app(WhatsAppClient::class);
        $response = $client->template('order_status')
            ->to('+919876543210')
            ->bodyParams(['John', 'Shipped', 'ORD-1234'])
            ->send();

        $this->assertTrue($response->isSuccess());
        $this->assertEquals('wamid.tpl456', $response->messageId);
    }

    /** @test */
    public function it_can_mock_status_check_response()
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'message_id' => 'wamid.status789',
                'status' => 'delivered',
            ], 200),
        ]);

        $client = app(WhatsAppClient::class);
        $response = $client->getStatus('wamid.status789');

        $this->assertTrue($response->isSuccess());
        $this->assertEquals('delivered', $response->status);
    }

    /** @test */
    public function it_can_mock_templates_list_response()
    {
        Http::fake([
            '*' => Http::response([
                'data' => [
                    ['name' => 'test_template', 'status' => 'APPROVED', 'language' => 'en_US'],
                ],
                'paging' => ['next_cursor' => null],
            ], 200),
        ]);

        $client = app(WhatsAppClient::class);
        $templates = $client->getTemplates();

        $this->assertCount(1, $templates['data']);
        $this->assertEquals('test_template', $templates['data'][0]['name']);
    }

    /** @test */
    public function it_asserts_request_payload_structure()
    {
        Http::fake([
            '*' => Http::response(['success' => true, 'message_id' => 'wamid.1'], 200),
        ]);

        $client = app(WhatsAppClient::class);
        $client->template('order_status')
            ->to('+919876543210')
            ->language('en_US')
            ->bodyParams(['John', 'Shipped', 'ORD-1234'])
            ->headerImage('https://example.com/img.jpg')
            ->buttonUrl('track')
            ->send();

        Http::assertSent(function ($request) {
            $body = $request->data();
            return isset($body['to'])
                && isset($body['phoneNoId'])
                && isset($body['type'])
                && isset($body['name'])
                && isset($body['language'])
                && isset($body['bodyParams'])
                && isset($body['headerParams'])
                && isset($body['buttons'])
                && $body['type'] === 'template'
                && $body['name'] === 'order_status'
                && count($body['bodyParams']) === 3
                && $body['headerParams'][0]['type'] === 'image'
                && $body['buttons'][0]['sub_type'] === 'url';
        });
    }

    /** @test */
    public function it_preserves_raw_response_data()
    {
        $rawResponse = [
            'success' => true,
            'message_id' => 'wamid.raw',
            'status' => 'queued',
            'extra_field' => 'preserved',
            'nested' => ['data' => 'value'],
        ];

        Http::fake([
            '*' => Http::response($rawResponse, 200),
        ]);

        $client = app(WhatsAppClient::class);
        $response = $client->sendText('+919876543210', 'Test');

        $this->assertEquals($rawResponse, $response->raw);
        $this->assertEquals('preserved', $response->raw['extra_field']);
        $this->assertEquals('value', $response->raw['nested']['data']);
    }
}
