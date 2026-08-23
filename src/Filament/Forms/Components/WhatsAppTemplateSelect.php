<?php

namespace AmravatiSMS\LaravelWhatsApp\Filament\Forms\Components;

use Filament\Forms\Components\Select;
use AmravatiSMS\LaravelWhatsApp\Models\WhatsappTemplate;

class WhatsAppTemplateSelect extends Select
{
    protected string $view = 'filament-forms::components.select';

    public static function make(string $name): static
    {
        return parent::make($name)
            ->options(fn () => WhatsappTemplate::approved()->pluck('name', 'name'))
            ->searchable()
            ->preload();
    }

    public function withPreview(): static
    {
        return $this->hintAction(
            \Filament\Forms\Components\Actions\Action::make('preview')
                ->icon('heroicon-o-eye')
                ->modalContent(function ($state) {
                    $template = WhatsappTemplate::findByName($state);
                    return $template ? nl2br($template->preview()) : 'Select a template first.';
                })
                ->modalSubmitAction(false)
        );
    }
}
