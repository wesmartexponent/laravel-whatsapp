<?php

namespace AmravatiSMS\LaravelWhatsApp\Tests\Unit;

use AmravatiSMS\LaravelWhatsApp\Tests\TestCase;
use AmravatiSMS\LaravelWhatsApp\Models\WhatsappTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WhatsappTemplateModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_template()
    {
        $template = WhatsappTemplate::create([
            'name' => 'order_status',
            'language' => 'en_US',
            'category' => 'UTILITY',
            'status' => 'APPROVED',
            'components' => [
                ['type' => 'BODY', 'text' => 'Hello {{1}}, your order {{2}} is {{3}}.'],
            ],
            'body_params_count' => 3,
        ]);

        $this->assertDatabaseHas('whatsapp_templates', [
            'name' => 'order_status',
            'language' => 'en_US',
        ]);
    }

    /** @test */
    public function it_can_scope_approved_templates()
    {
        WhatsappTemplate::create(['name' => 't1', 'status' => 'APPROVED']);
        WhatsappTemplate::create(['name' => 't2', 'status' => 'PENDING']);
        WhatsappTemplate::create(['name' => 't3', 'status' => 'REJECTED']);

        $approved = WhatsappTemplate::approved()->get();

        $this->assertCount(1, $approved);
        $this->assertEquals('t1', $approved->first()->name);
    }

    /** @test */
    public function it_detects_approval_status()
    {
        $approved = new WhatsappTemplate(['status' => 'APPROVED']);
        $pending = new WhatsappTemplate(['status' => 'PENDING']);
        $rejected = new WhatsappTemplate(['status' => 'REJECTED']);

        $this->assertTrue($approved->isApproved());
        $this->assertTrue($pending->isPending());
        $this->assertTrue($rejected->isRejected());
        $this->assertFalse($approved->isPending());
    }

    /** @test */
    public function it_counts_required_body_params()
    {
        $template = new WhatsappTemplate([
            'components' => [
                ['type' => 'BODY', 'text' => 'Hello {{1}}, your order {{2}} is {{3}}.'],
            ],
        ]);

        $params = $template->requiredBodyParams();

        $this->assertCount(3, $params);
        $this->assertArrayHasKey('1', $params);
        $this->assertArrayHasKey('2', $params);
        $this->assertArrayHasKey('3', $params);
    }

    /** @test */
    public function it_validates_params()
    {
        $template = new WhatsappTemplate([
            'name' => 'order_status',
            'components' => [
                ['type' => 'BODY', 'text' => 'Hello {{1}}, your order {{2}} is {{3}}.'],
            ],
            'body_params_count' => 3,
        ]);

        $this->assertTrue($template->validateParams(['John', 'Shipped', 'ORD-1234']));
    }

    /** @test */
    public function it_throws_on_insufficient_params()
    {
        $this->expectException(\InvalidArgumentException::class);

        $template = new WhatsappTemplate([
            'name' => 'order_status',
            'components' => [
                ['type' => 'BODY', 'text' => 'Hello {{1}}, your order {{2}} is {{3}}.'],
            ],
            'body_params_count' => 3,
        ]);

        $template->validateParams(['John']);
    }

    /** @test */
    public function it_generates_preview()
    {
        $template = new WhatsappTemplate([
            'components' => [
                ['type' => 'HEADER', 'text' => 'Order Update'],
                ['type' => 'BODY', 'text' => 'Hello {{1}}, your order is ready.'],
                ['type' => 'FOOTER', 'text' => 'Thank you for shopping!'],
            ],
        ]);

        $preview = $template->preview();

        $this->assertStringContainsString('Order Update', $preview);
        $this->assertStringContainsString('Hello {{1}}, your order is ready.', $preview);
        $this->assertStringContainsString('Thank you for shopping!', $preview);
    }

    /** @test */
    public function it_detects_header_type()
    {
        $withImage = new WhatsappTemplate(['header_type' => 'IMAGE']);
        $withText = new WhatsappTemplate(['header_type' => 'TEXT']);
        $none = new WhatsappTemplate(['header_type' => null]);

        $this->assertTrue($withImage->hasHeader());
        $this->assertTrue($withImage->hasHeaderImage());
        $this->assertFalse($withImage->hasHeaderText());

        $this->assertTrue($withText->hasHeaderText());
        $this->assertFalse($none->hasHeader());
    }
}
