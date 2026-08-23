<?php

namespace AmravatiSMS\LaravelWhatsApp\Tests\Feature;

use AmravatiSMS\LaravelWhatsApp\Tests\TestCase;
use AmravatiSMS\LaravelWhatsApp\Models\WhatsappTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;

class TemplateSyncTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_syncs_templates_from_api()
    {
        Http::fake([
            '*' => Http::response([
                'data' => [
                    [
                        'name' => 'hello_world',
                        'language' => 'en_US',
                        'category' => 'UTILITY',
                        'status' => 'APPROVED',
                        'components' => [
                            ['type' => 'BODY', 'text' => 'Hello {{1}}!'],
                        ],
                    ],
                    [
                        'name' => 'order_status',
                        'language' => 'en_US',
                        'category' => 'UTILITY',
                        'status' => 'APPROVED',
                        'components' => [
                            ['type' => 'BODY', 'text' => 'Hi {{1}}, your order {{2}} is {{3}}.'],
                        ],
                    ],
                ],
                'paging' => ['next_cursor' => null],
            ], 200),
        ]);

        Artisan::call('amravati:templates:sync');

        $this->assertDatabaseCount('whatsapp_templates', 2);
        $this->assertDatabaseHas('whatsapp_templates', [
            'name' => 'hello_world',
            'body_params_count' => 1,
        ]);
        $this->assertDatabaseHas('whatsapp_templates', [
            'name' => 'order_status',
            'body_params_count' => 3,
        ]);
    }

    /** @test */
    public function it_handles_paginated_template_responses()
    {
        Http::fake([
            '*/templates?limit=100' => Http::response([
                'data' => [
                    ['name' => 'page1_template', 'language' => 'en_US', 'status' => 'APPROVED', 'components' => []],
                ],
                'paging' => ['next_cursor' => 'cursor123'],
            ], 200),
            '*/templates?limit=100&after=cursor123' => Http::response([
                'data' => [
                    ['name' => 'page2_template', 'language' => 'en_US', 'status' => 'APPROVED', 'components' => []],
                ],
                'paging' => ['next_cursor' => null],
            ], 200),
        ]);

        Artisan::call('amravati:templates:sync');

        $this->assertDatabaseCount('whatsapp_templates', 2);
        $this->assertDatabaseHas('whatsapp_templates', ['name' => 'page1_template']);
        $this->assertDatabaseHas('whatsapp_templates', ['name' => 'page2_template']);
    }

    /** @test */
    public function it_updates_existing_templates_on_sync()
    {
        WhatsappTemplate::create([
            'name' => 'hello_world',
            'language' => 'en_US',
            'status' => 'PENDING',
            'body_params_count' => 0,
            'components' => [],
        ]);

        Http::fake([
            '*' => Http::response([
                'data' => [
                    [
                        'name' => 'hello_world',
                        'language' => 'en_US',
                        'status' => 'APPROVED',
                        'components' => [
                            ['type' => 'BODY', 'text' => 'Hello {{1}}!'],
                        ],
                    ],
                ],
                'paging' => ['next_cursor' => null],
            ], 200),
        ]);

        Artisan::call('amravati:templates:sync');

        $template = WhatsappTemplate::findByName('hello_world');
        $this->assertEquals('APPROVED', $template->status);
        $this->assertEquals(1, $template->body_params_count);
    }

    /** @test */
    public function it_detects_header_types_correctly()
    {
        Http::fake([
            '*' => Http::response([
                'data' => [
                    [
                        'name' => 'image_template',
                        'language' => 'en_US',
                        'status' => 'APPROVED',
                        'components' => [
                            ['type' => 'HEADER', 'format' => 'IMAGE'],
                            ['type' => 'BODY', 'text' => 'Check out {{1}}!'],
                        ],
                    ],
                    [
                        'name' => 'text_template',
                        'language' => 'en_US',
                        'status' => 'APPROVED',
                        'components' => [
                            ['type' => 'HEADER', 'format' => 'TEXT', 'text' => 'Welcome {{1}}'],
                            ['type' => 'BODY', 'text' => 'Your order is ready.'],
                        ],
                    ],
                ],
                'paging' => ['next_cursor' => null],
            ], 200),
        ]);

        Artisan::call('amravati:templates:sync');

        $imageTemplate = WhatsappTemplate::findByName('image_template');
        $this->assertEquals('IMAGE', $imageTemplate->header_type);
        $this->assertTrue($imageTemplate->hasHeaderImage());

        $textTemplate = WhatsappTemplate::findByName('text_template');
        $this->assertEquals('TEXT', $textTemplate->header_type);
        $this->assertTrue($textTemplate->hasHeaderText());
    }
}
