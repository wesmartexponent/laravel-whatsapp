<?php

namespace AmravatiSMS\LaravelWhatsApp\Tests\Feature;

use AmravatiSMS\LaravelWhatsApp\Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;

class CommandsTest extends TestCase
{
    /** @test */
    public function install_command_publishes_config()
    {
        $this->artisan('amravati:install')
            ->assertSuccessful();
    }

    /** @test */
    public function test_command_checks_configuration()
    {
        $this->artisan('amravati:test')
            ->assertSuccessful()
            ->expectsOutput('Checking configuration...');
    }

    /** @test */
    public function send_text_command_sends_message()
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'message_id' => 'wamid.cli123',
                'status' => 'queued',
            ], 200),
        ]);

        $this->artisan('amravati:send:text', [
            'to' => '+919876543210',
            'message' => 'Hello from CLI',
        ])
            ->assertSuccessful()
            ->expectsOutput('Message sent! ID: wamid.cli123');
    }

    /** @test */
    public function send_template_command_sends_template()
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'message_id' => 'wamid.tplcli',
                'status' => 'queued',
            ], 200),
        ]);

        $this->artisan('amravati:send:template', [
            'to' => '+919876543210',
            'template' => 'order_status',
            '--params' => 'John,Shipped,ORD-1234',
        ])
            ->assertSuccessful()
            ->expectsOutput('Template sent! ID: wamid.tplcli');
    }

    /** @test */
    public function status_command_shows_message_status()
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'message_id' => 'wamid.123',
                'status' => 'delivered',
            ], 200),
        ]);

        $this->artisan('amravati:status', ['messageId' => 'wamid.123'])
            ->assertSuccessful();
    }
}
