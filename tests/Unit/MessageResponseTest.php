<?php

namespace AmravatiSMS\LaravelWhatsApp\Tests\Unit;

use AmravatiSMS\LaravelWhatsApp\Tests\TestCase;
use AmravatiSMS\LaravelWhatsApp\Responses\MessageResponse;

class MessageResponseTest extends TestCase
{
    /** @test */
    public function it_creates_response_from_successful_api_response()
    {
        $data = [
            'success' => true,
            'message_id' => 'wamid.HBgMOTE5ODc2NTQzMjEwFQIAERgSODBCNEM1QjY0RjA4M0E4MjEA',
            'status' => 'queued',
        ];

        $response = new MessageResponse($data);

        $this->assertTrue($response->isSuccess());
        $this->assertEquals('wamid.HBgMOTE5ODc2NTQzMjEwFQIAERgSODBCNEM1QjY0RjA4M0E4MjEA', $response->messageId);
        $this->assertEquals('queued', $response->status);
        $this->assertFalse($response->isQueued());
        $this->assertFalse($response->hasError());
    }

    /** @test */
    public function it_creates_response_from_queued_message()
    {
        $data = [
            'success' => true,
            'queued' => true,
            'message' => 'Message queued for delivery.',
        ];

        $response = new MessageResponse($data);

        $this->assertTrue($response->isSuccess());
        $this->assertTrue($response->isQueued());
        $this->assertNull($response->messageId);
    }

    /** @test */
    public function it_creates_response_from_failed_api_response()
    {
        $data = [
            'success' => false,
            'error' => [
                'message' => 'Invalid phone number',
                'code' => 1006,
            ],
        ];

        $response = new MessageResponse($data);

        $this->assertFalse($response->isSuccess());
        $this->assertTrue($response->hasError());
        $this->assertEquals('Invalid phone number', $response->getErrorMessage());
    }

    /** @test */
    public function it_converts_to_array()
    {
        $data = [
            'success' => true,
            'message_id' => 'wamid.123',
            'status' => 'sent',
        ];

        $response = new MessageResponse($data);
        $array = $response->toArray();

        $this->assertArrayHasKey('success', $array);
        $this->assertArrayHasKey('message_id', $array);
        $this->assertArrayHasKey('raw', $array);
    }
}
