<?php

namespace AmravatiSMS\LaravelWhatsApp\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use AmravatiSMS\LaravelWhatsApp\Facades\AmravatiSMS;
use AmravatiSMS\LaravelWhatsApp\Models\WhatsappTemplate;

class BulkSendPage extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'WhatsApp';
    protected static ?string $navigationLabel = 'Bulk Send';
    protected static ?string $title = 'Bulk WhatsApp Send';
    protected static string $view = 'amravati-whatsapp::filament.pages.bulk-send';
    protected static ?int $navigationSort = 4;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Upload CSV')
                    ->description('CSV must contain a "phone" column. Optional: template parameter columns.')
                    ->schema([
                        Forms\Components\FileUpload::make('csv')
                            ->label('CSV File')
                            ->acceptedFileTypes(['text/csv'])
                            ->required(),
                    ]),

                Forms\Components\Section::make('Message')
                    ->schema([
                        Forms\Components\Select::make('template')
                            ->label('Template')
                            ->options(fn () => WhatsappTemplate::approved()->pluck('name', 'name'))
                            ->searchable()
                            ->required()
                            ->live(),

                        Forms\Components\Repeater::make('column_mapping')
                            ->label('Column Mapping')
                            ->schema([
                                Forms\Components\TextInput::make('param_index')
                                    ->label('Parameter #')
                                    ->numeric()
                                    ->required(),
                                Forms\Components\TextInput::make('csv_column')
                                    ->label('CSV Column Name')
                                    ->required(),
                            ])
                            ->visible(fn (Forms\Get $get): bool => !empty($get('template'))),
                    ]),
            ])
            ->statePath('data');
    }

    public function sendBulk(): void
    {
        $data = $this->form->getState()['data'];

        Notification::make()
            ->title('Bulk send started')
            ->body('Messages are being queued for delivery.')
            ->info()
            ->send();

        // Implementation would read CSV and dispatch jobs
        // This is a scaffold — actual CSV parsing logic goes here
    }
}
