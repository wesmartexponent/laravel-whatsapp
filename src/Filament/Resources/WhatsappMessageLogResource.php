<?php

namespace AmravatiSMS\LaravelWhatsApp\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WhatsappMessageLogResource extends Resource
{
    protected static ?string $model = null;
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationGroup = 'WhatsApp';
    protected static ?string $navigationLabel = 'Message Logs';
    protected static ?int $navigationSort = 2;

    public static function getModel(): string
    {
        return 'App\\Models\\WhatsappMessageLog';
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $table = config('amravati-whatsapp.logging.table', 'whatsapp_message_logs');
        return DB::table($table)->query();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('phone_number'),
                Forms\Components\TextInput::make('type'),
                Forms\Components\TextInput::make('status'),
                Forms\Components\KeyValue::make('payload'),
                Forms\Components\KeyValue::make('response'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('phone_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('template_name')
                    ->searchable()
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match (strtolower($state)) {
                        'sent' => 'info',
                        'delivered' => 'success',
                        'read' => 'primary',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\IconColumn::make('success')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'sent' => 'Sent',
                        'delivered' => 'Delivered',
                        'read' => 'Read',
                        'failed' => 'Failed',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'text' => 'Text',
                        'image' => 'Image',
                        'video' => 'Video',
                        'document' => 'Document',
                        'audio' => 'Audio',
                        'template' => 'Template',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => \Filament\Resources\Pages\ListRecords::route('/'),
        ];
    }
}
