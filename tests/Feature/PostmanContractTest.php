<?php

namespace AmravatiSMS\LaravelWhatsApp\Tests\Feature;

use AmravatiSMS\LaravelWhatsApp\Tests\TestCase;
use AmravatiSMS\LaravelWhatsApp\Client\WhatsAppClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * Pins the SDK to the published "WhatsApp Messaging API" Postman collection.
 *
 * Each test mirrors one request from that collection: same endpoint, same JSON body.
 * If the API contract changes, these fail first.
 */
class PostmanContractTest extends TestCase
{
    protected WhatsAppClient $client;

    protected const BASE = 'https://automate.amravatisms.com';
    protected const MESSAGES = self::BASE . '/v2/whatsapp-business/messages';

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = app(WhatsAppClient::class);

        Http::fake([
            '*' => Http::response(['success' => true, 'message_id' => 'wamid.1'], 200),
        ]);
    }

    protected function lastRequest(): Request
    {
        // recorded() returns a Collection on Laravel 10+; wrap it so this holds
        // across the whole supported matrix.
        $recorded = collect(Http::recorded());
        $this->assertNotEmpty($recorded, 'No HTTP request was recorded.');

        return $recorded->last()[0];
    }

    protected function assertRequest(string $url, array $body): void
    {
        $request = $this->lastRequest();

        $this->assertEquals($url, $request->url());
        $this->assertEquals($body, $request->data());
    }

    /** @test */
    public function get_message_status_matches_collection()
    {
        $this->client->getStatus('wamid.123456');

        $this->assertEquals(
            self::BASE . '/v2/whatsapp-business/status/wamid.123456',
            $this->lastRequest()->url()
        );
        $this->assertEquals('GET', $this->lastRequest()->method());
    }

    /** @test */
    public function get_templates_uses_the_documented_v2_prefix()
    {
        $this->client->getTemplates(limit: 100);

        // The collection exposes every endpoint under /v2/whatsapp-business/ — no /api prefix.
        $this->assertStringStartsWith(
            self::BASE . '/v2/whatsapp-business/templates',
            $this->lastRequest()->url()
        );
        $this->assertStringNotContainsString('/api/v2/', $this->lastRequest()->url());
    }

    /** @test */
    public function send_text_message_matches_collection()
    {
        $this->client->sendText('14155552671', 'Hello from Postman!');

        $this->assertRequest(self::MESSAGES, [
            'to' => '14155552671',
            'phoneNoId' => '1234567890',
            'type' => 'text',
            'text' => 'Hello from Postman!',
        ]);
    }

    /** @test */
    public function send_image_by_url_matches_collection()
    {
        $this->client->sendImageByUrl('14155552671', 'https://example.com/image.jpg', 'Nice picture!');

        $this->assertRequest(self::MESSAGES, [
            'to' => '14155552671',
            'phoneNoId' => '1234567890',
            'type' => 'image',
            'url' => 'https://example.com/image.jpg',
            'caption' => 'Nice picture!',
        ]);
    }

    /** @test */
    public function send_image_by_id_matches_collection()
    {
        $this->client->sendImageById('14155552671', 'MEDIA_ID_123', 'Image from media library');

        $this->assertRequest(self::MESSAGES, [
            'to' => '14155552671',
            'phoneNoId' => '1234567890',
            'type' => 'image',
            'id' => 'MEDIA_ID_123',
            'caption' => 'Image from media library',
        ]);
    }

    /** @test */
    public function send_video_by_url_matches_collection()
    {
        $this->client->sendVideoByUrl('14155552671', 'https://example.com/video.mp4', 'Check out this video!');

        $this->assertRequest(self::MESSAGES, [
            'to' => '14155552671',
            'phoneNoId' => '1234567890',
            'type' => 'video',
            'url' => 'https://example.com/video.mp4',
            'caption' => 'Check out this video!',
        ]);
    }

    /** @test */
    public function send_document_matches_collection()
    {
        $this->client->sendDocument(
            '14155552671',
            'https://example.com/document.pdf',
            'invoice.pdf',
            'Important document'
        );

        $this->assertRequest(self::MESSAGES, [
            'to' => '14155552671',
            'phoneNoId' => '1234567890',
            'type' => 'document',
            'url' => 'https://example.com/document.pdf',
            'filename' => 'invoice.pdf',
            'caption' => 'Important document',
        ]);
    }

    /** @test */
    public function send_audio_matches_collection()
    {
        $this->client->sendAudio('14155552671', 'https://example.com/audio.mp3');

        $this->assertRequest(self::MESSAGES, [
            'to' => '14155552671',
            'phoneNoId' => '1234567890',
            'type' => 'audio',
            'url' => 'https://example.com/audio.mp3',
        ]);
    }

    /** @test */
    public function basic_text_template_matches_collection()
    {
        $this->client->template('hello_world')->to('14155552671')->language('en_US')->send();

        $this->assertRequest(self::MESSAGES, [
            'to' => '14155552671',
            'phoneNoId' => '1234567890',
            'type' => 'template',
            'name' => 'hello_world',
            'language' => 'en_US',
        ]);
    }

    /** @test */
    public function template_with_body_variables_matches_collection()
    {
        $this->client->template('order_status')
            ->to('14155552671')
            ->bodyParams(['John Doe', 'shipped', '12345'])
            ->send();

        $this->assertRequest(self::MESSAGES, [
            'to' => '14155552671',
            'phoneNoId' => '1234567890',
            'type' => 'template',
            'name' => 'order_status',
            'language' => 'en_US',
            'bodyParams' => ['John Doe', 'shipped', '12345'],
        ]);
    }

    /** @test */
    public function template_with_header_image_matches_collection()
    {
        $this->client->template('promotional_image')
            ->to('14155552671')
            ->headerImage('https://example.com/promo-image.jpg')
            ->bodyParams(['John', '50% OFF'])
            ->send();

        $this->assertRequest(self::MESSAGES, [
            'to' => '14155552671',
            'phoneNoId' => '1234567890',
            'type' => 'template',
            'name' => 'promotional_image',
            'language' => 'en_US',
            'bodyParams' => ['John', '50% OFF'],
            'headerParams' => [
                ['type' => 'image', 'url' => 'https://example.com/promo-image.jpg'],
            ],
        ]);
    }

    /** @test */
    public function template_with_header_video_by_id_matches_collection()
    {
        $this->client->template('video_template')
            ->to('14155552671')
            ->headerVideo('VIDEO_MEDIA_ID_123', isId: true)
            ->send();

        $this->assertRequest(self::MESSAGES, [
            'to' => '14155552671',
            'phoneNoId' => '1234567890',
            'type' => 'template',
            'name' => 'video_template',
            'language' => 'en_US',
            'headerParams' => [
                ['type' => 'video', 'id' => 'VIDEO_MEDIA_ID_123'],
            ],
        ]);
    }

    /** @test */
    public function template_with_header_location_matches_collection()
    {
        $this->client->template('location_template')
            ->to('14155552671')
            ->headerLocation(37.7749, -122.4194, 'San Francisco', 'San Francisco, CA, USA')
            ->send();

        $this->assertRequest(self::MESSAGES, [
            'to' => '14155552671',
            'phoneNoId' => '1234567890',
            'type' => 'template',
            'name' => 'location_template',
            'language' => 'en_US',
            'headerParams' => [[
                'type' => 'location',
                'latitude' => 37.7749,
                'longitude' => -122.4194,
                'name' => 'San Francisco',
                'address' => 'San Francisco, CA, USA',
            ]],
        ]);
    }

    /** @test */
    public function template_with_currency_parameter_matches_collection()
    {
        $this->client->template('payment_receipt')
            ->to('14155552671')
            ->bodyParam('John Doe')
            ->bodyCurrency(99500, 'USD', '$99.50')
            ->send();

        $this->assertRequest(self::MESSAGES, [
            'to' => '14155552671',
            'phoneNoId' => '1234567890',
            'type' => 'template',
            'name' => 'payment_receipt',
            'language' => 'en_US',
            'bodyParams' => [
                'John Doe',
                [
                    'type' => 'currency',
                    'amount_1000' => 99500,
                    'code' => 'USD',
                    'fallback_value' => '$99.50',
                ],
            ],
        ]);
    }

    /** @test */
    public function template_with_datetime_parameter_matches_collection()
    {
        $this->client->template('appointment_reminder')
            ->to('14155552671')
            ->bodyParam('John Doe')
            ->bodyDateTime('July 25, 2025 at 3:00 PM')
            ->send();

        $this->assertRequest(self::MESSAGES, [
            'to' => '14155552671',
            'phoneNoId' => '1234567890',
            'type' => 'template',
            'name' => 'appointment_reminder',
            'language' => 'en_US',
            'bodyParams' => [
                'John Doe',
                [
                    'type' => 'date_time',
                    'fallback_value' => 'July 25, 2025 at 3:00 PM',
                ],
            ],
        ]);
    }

    /** @test */
    public function auth_template_with_url_button_matches_collection()
    {
        $this->client->template('auth_template')
            ->to('14155552671')
            ->bodyParams(['567432'])
            ->buttonUrl('567432')
            ->send();

        $this->assertRequest(self::MESSAGES, [
            'to' => '14155552671',
            'phoneNoId' => '1234567890',
            'type' => 'template',
            'name' => 'auth_template',
            'language' => 'en_US',
            'bodyParams' => ['567432'],
            'buttons' => [
                ['type' => 'button', 'sub_type' => 'url', 'text' => '567432'],
            ],
        ]);
    }

    /** @test */
    public function catalog_template_matches_collection()
    {
        $this->client->template('catalog_template')
            ->to('14155552671')
            ->bodyParams(['John Doe', 'Summer Collection'])
            ->buttonCatalog('product_123')
            ->send();

        $this->assertRequest(self::MESSAGES, [
            'to' => '14155552671',
            'phoneNoId' => '1234567890',
            'type' => 'template',
            'name' => 'catalog_template',
            'language' => 'en_US',
            'bodyParams' => ['John Doe', 'Summer Collection'],
            'buttons' => [
                ['type' => 'button', 'sub_type' => 'catalog', 'thumbnail_product_retailer_id' => 'product_123'],
            ],
        ]);
    }

    /** @test */
    public function flow_template_matches_collection()
    {
        $this->client->template('flow_template')
            ->to('14155552671')
            ->bodyParams(['John Doe', 'Customer Survey'])
            ->buttonFlow()
            ->send();

        $this->assertRequest(self::MESSAGES, [
            'to' => '14155552671',
            'phoneNoId' => '1234567890',
            'type' => 'template',
            'name' => 'flow_template',
            'language' => 'en_US',
            'bodyParams' => ['John Doe', 'Customer Survey'],
            'buttons' => [
                ['type' => 'button', 'sub_type' => 'flow'],
            ],
        ]);
    }

    /** @test */
    public function every_request_carries_the_documented_auth_header()
    {
        $this->client->sendText('14155552671', 'Hello');

        $this->assertTrue(
            $this->lastRequest()->hasHeader('Authorization', 'Bearer test_api_key_12345')
        );
        $this->assertTrue(
            $this->lastRequest()->hasHeader('Content-Type', 'application/json')
        );
    }
}
