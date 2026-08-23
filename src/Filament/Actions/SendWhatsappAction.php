<?php

namespace AmravatiSMS\LaravelWhatsApp\Filament\Actions;

use Filament\Tables\Actions\Action;
use AmravatiSMS\LaravelWhatsApp\Facades\AmravatiSMS;

class SendWhatsappAction
{
    public static function make(): Action
    {
        return Action::make('send_whatsapp')
            ->label('Send WhatsApp')
            ->icon('heroicon-o-chat-bubble-left-right')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Send WhatsApp Message')
            ->modalDescription('Are you sure you want to send this WhatsApp message?')
            ->action(function ($record, array $data) {
                // Implementation depends on context
                // This is a scaffold action to be customized per resource
            });
    }
}
