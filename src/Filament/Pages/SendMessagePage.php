<?php

namespace AmravatiSMS\LaravelWhatsApp\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use AmravatiSMS\LaravelWhatsApp\Facades\AmravatiSMS;
use AmravatiSMS\LaravelWhatsApp\Models\WhatsappTemplate;

class SendMessagePage extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';
    protected static ?string $navigationGroup = 'WhatsApp';
    protected static ?string $navigationLabel = 'Send Message';
    protected static ?string $title = 'Send WhatsApp Message';
    protected static string $view = 'amravati-whatsapp::filament.pages.send-message';
    protected static ?int $navigationSort = 3;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Recipient')
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->label('Phone Number')
                            ->placeholder('+91 98765 43210')
                            ->required()
                            ->tel(),
                    ]),

                Forms\Components\Section::make('Message')
                    ->schema([
                        Forms\Components\Select::make('message_type')
                            ->label('Message Type')
                            ->options([
                                'text' => 'Text',
                                'image' => 'Image (URL)',
                                'video' => 'Video (URL)',
                                'document' => 'Document',
                                'audio' => 'Audio',
                                'template' => 'Template',
                            ])
                            ->required()
                            ->live(),

                        Forms\Components\Textarea::make('text')
                            ->label('Message Text')
                            ->required()
                            ->visible(fn (Forms\Get $get): bool => $get('message_type') === 'text'),

                        Forms\Components\TextInput::make('url')
                            ->label('Media URL')
                            ->url()
                            ->required()
                            ->visible(fn (Forms\Get $get): bool => in_array($get('message_type'), ['image', 'video', 'document', 'audio'])),

                        Forms\Components\TextInput::make('caption')
                            ->label('Caption')
                            ->visible(fn (Forms\Get $get): bool => in_array($get('message_type'), ['image', 'video', 'document'])),

                        Forms\Components\TextInput::make('filename')
                            ->label('Filename')
                            ->visible(fn (Forms\Get $get): bool => $get('message_type') === 'document'),

                        Forms\Components\Select::make('template')
                            ->label('Template')
                            ->options(fn () => WhatsappTemplate::approved()->pluck('name', 'name'))
                            ->searchable()
                            ->required()
                            ->live()
                            ->visible(fn (Forms\Get $get): bool => $get('message_type') === 'template'),

                        Forms\Components\Repeater::make('template_params')
                            ->label('Template Parameters')
                            ->schema([
                                Forms\Components\TextInput::make('value')
                                    ->required(),
                            ])
                            ->visible(fn (Forms\Get $get): bool => $get('message_type') === 'template' && !empty($get('template')))
                            ->defaultItems(fn (Forms\Get $get): int => WhatsappTemplate::findByName($get('template'))?->body_params_count ?? 0),
                    ]),
            ])
            ->statePath('data');
    }

    public function send(): void
    {
        $data = $this->form->getState()['data'];
        $phone = $data['phone'];
        $type = $data['message_type'];

        try {
            $response = match ($type) {
                'text' => AmravatiSMS::sendText($phone, $data['text']),
                'image' => AmravatiSMS::sendImageByUrl($phone, $data['url'], $data['caption'] ?? null),
                'video' => AmravatiSMS::sendVideoByUrl($phone, $data['url'], $data['caption'] ?? null),
                'document' => AmravatiSMS::sendDocument($phone, $data['url'], $data['filename'], $data['caption'] ?? null),
                'audio' => AmravatiSMS::sendAudio($phone, $data['url']),
                'template' => AmravatiSMS::template($data['template'])
                    ->to($phone)
                    ->bodyParams(collect($data['template_params'] ?? [])->pluck('value')->toArray())
                    ->send(),
            };

            if ($response->isSuccess()) {
                Notification::make()
                    ->title('Message sent!')
                    ->body("Message ID: {$response->messageId}")
                    ->success()
                    ->send();

                $this->form->fill();
            } else {
                Notification::make()
                    ->title('Failed to send')
                    ->body($response->getErrorMessage())
                    ->danger()
                    ->send();
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
