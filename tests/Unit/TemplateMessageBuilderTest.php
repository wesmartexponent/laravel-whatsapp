<?php

namespace AmravatiSMS\LaravelWhatsApp\Tests\Unit;

use AmravatiSMS\LaravelWhatsApp\Tests\TestCase;
use AmravatiSMS\LaravelWhatsApp\Builders\TemplateMessageBuilder;
use AmravatiSMS\LaravelWhatsApp\Client\WhatsAppClient;

class TemplateMessageBuilderTest extends TestCase
{
    protected WhatsAppClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = app(WhatsAppClient::class);
    }

    /** @test */
    public function it_builds_basic_template_payload()
    {
        $builder = new TemplateMessageBuilder($this->client, 'hello_world', '1234567890');
        $builder->to('919876543210')->language('en_US');

        $payload = $builder->toArray();

        $this->assertEquals('919876543210', $payload['to']);
        $this->assertEquals('1234567890', $payload['phoneNoId']);
        $this->assertEquals('template', $payload['type']);
        $this->assertEquals('hello_world', $payload['name']);
        $this->assertEquals('en_US', $payload['language']);
    }

    /** @test */
    public function it_builds_template_with_body_params()
    {
        $builder = new TemplateMessageBuilder($this->client, 'order_status', '1234567890');
        $builder->to('919876543210')
            ->bodyParams(['John', 'Shipped', 'ORD-1234']);

        $payload = $builder->toArray();

        $this->assertEquals(['John', 'Shipped', 'ORD-1234'], $payload['bodyParams']);
    }

    /** @test */
    public function it_builds_template_with_header_image()
    {
        $builder = new TemplateMessageBuilder($this->client, 'promo', '1234567890');
        $builder->to('919876543210')
            ->headerImage('https://example.com/promo.jpg');

        $payload = $builder->toArray();

        $this->assertEquals([['type' => 'image', 'url' => 'https://example.com/promo.jpg']], $payload['headerParams']);
    }

    /** @test */
    public function it_builds_template_with_header_location()
    {
        $builder = new TemplateMessageBuilder($this->client, 'store_locator', '1234567890');
        $builder->to('919876543210')
            ->headerLocation(20.9320, 77.7523, 'Amravati Store', 'Amravati, Maharashtra');

        $payload = $builder->toArray();

        $this->assertEquals([[
            'type' => 'location',
            'latitude' => 20.9320,
            'longitude' => 77.7523,
            'name' => 'Amravati Store',
            'address' => 'Amravati, Maharashtra',
        ]], $payload['headerParams']);
    }

    /** @test */
    public function it_builds_template_with_url_button()
    {
        $builder = new TemplateMessageBuilder($this->client, 'auth_template', '1234567890');
        $builder->to('919876543210')
            ->bodyParams(['567432'])
            ->buttonUrl('567432');

        $payload = $builder->toArray();

        $this->assertEquals([[
            'type' => 'button',
            'sub_type' => 'url',
            'text' => '567432',
        ]], $payload['buttons']);
    }

    /** @test */
    public function it_builds_template_with_catalog_button()
    {
        $builder = new TemplateMessageBuilder($this->client, 'catalog_template', '1234567890');
        $builder->to('919876543210')
            ->buttonCatalog('product_123');

        $payload = $builder->toArray();

        $this->assertEquals([[
            'type' => 'button',
            'sub_type' => 'catalog',
            'thumbnail_product_retailer_id' => 'product_123',
        ]], $payload['buttons']);
    }

    /** @test */
    public function it_throws_exception_when_sending_without_recipient()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Recipient phone number is required');

        $builder = new TemplateMessageBuilder($this->client, 'hello_world', '1234567890');
        $builder->send();
    }

    /** @test */
    public function it_normalizes_phone_number()
    {
        $builder = new TemplateMessageBuilder($this->client, 'hello_world', '1234567890');
        $builder->to('+91 98765 43210');

        $payload = $builder->toArray();

        $this->assertEquals('919876543210', $payload['to']);
    }
}
