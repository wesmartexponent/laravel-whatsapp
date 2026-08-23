<?php

namespace AmravatiSMS\LaravelWhatsApp\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use AmravatiSMS\LaravelWhatsApp\Models\WhatsappTemplate;
use AmravatiSMS\LaravelWhatsApp\Filament\Resources\WhatsappTemplateResource\Pages;

class WhatsappTemplateResource extends Resource
{
    protected static ?string $model = WhatsappTemplate::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'WhatsApp';
    protected static ?string $navigationLabel = 'Templates';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->disabled(),
                Forms\Components\TextInput::make('language')
                    ->required()
                    ->disabled(),
                Forms\Components\TextInput::make('category')
                    ->disabled(),
                Forms\Components\TextInput::make('status')
                    ->disabled(),
                Forms\Components\KeyValue::make('components')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('language')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'MARKETING' => 'warning',
                        'UTILITY' => 'success',
                        'AUTHENTICATION' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match (strtoupper($state)) {
                        'APPROVED' => 'success',
                        'PENDING' => 'warning',
                        'REJECTED' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('body_params_count')
                    ->label('Body Params')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'APPROVED' => 'Approved',
                        'PENDING' => 'Pending',
                        'REJECTED' => 'Rejected',
                    ]),
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'MARKETING' => 'Marketing',
                        'UTILITY' => 'Utility',
                        'AUTHENTICATION' => 'Authentication',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->icon('heroicon-o-eye')
                    ->modalContent(fn (WhatsappTemplate $record): string => nl2br($record->preview()))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                Tables\Actions\Action::make('sync')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->action(function (WhatsappTemplate $record) {
                        \Artisan::call('amravati:templates:sync');
                    })
                    ->visible(false), // Hidden per-row, use header action instead
            ])
            ->headerActions([
                Tables\Actions\Action::make('sync_all')
                    ->label('Sync Templates')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function () {
                        \Artisan::call('amravati:templates:sync');
                    })
                    ->after(function () {
                        \Filament\Notifications\Notification::make()
                            ->title('Templates synced successfully')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                //
  ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWhatsappTemplates::route('/'),
        ];
    }
}
