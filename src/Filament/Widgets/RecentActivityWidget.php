<?php

namespace AmravatiSMS\LaravelWhatsApp\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class RecentActivityWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $tableName = config('amravati-whatsapp.logging.table', 'whatsapp_message_logs');

        return $table
            ->query(
                DB::table($tableName)
                    ->orderByDesc('created_at')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('phone_number'),
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match (strtolower($state)) {
                        'sent' => 'info',
                        'delivered' => 'success',
                        'read' => 'primary',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->paginated(false);
    }
}
