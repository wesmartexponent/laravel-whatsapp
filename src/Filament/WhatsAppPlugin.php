<?php

namespace AmravatiSMS\LaravelWhatsApp\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class WhatsAppPlugin implements Plugin
{
    public function getId(): string
    {
        return 'amravati-whatsapp';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                Resources\WhatsappTemplateResource::class,
                Resources\WhatsappMessageLogResource::class,
            ])
            ->pages([
                Pages\SendMessagePage::class,
                Pages\BulkSendPage::class,
            ])
            ->widgets([
                Widgets\MessageStatsWidget::class,
                Widgets\RecentActivityWidget::class,
                Widgets\TemplateUsageWidget::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }
}
