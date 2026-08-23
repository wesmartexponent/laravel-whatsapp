<?php

namespace AmravatiSMS\LaravelWhatsApp\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class MessageStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $table = config('amravati-whatsapp.logging.table', 'whatsapp_message_logs');
        $today = now()->startOfDay();

        return [
            Stat::make('Sent Today', DB::table($table)->whereDate('created_at', $today)->count())
                ->description('Total messages sent today')
                ->icon('heroicon-o-paper-airplane')
                ->color('info'),

            Stat::make('Delivered', DB::table($table)->whereDate('created_at', $today)->where('status', 'delivered')->count())
                ->description('Successfully delivered')
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Failed', DB::table($table)->whereDate('created_at', $today)->where('status', 'failed')->count())
                ->description('Failed deliveries')
                ->icon('heroicon-o-x-circle')
                ->color('danger'),

            Stat::make('Read', DB::table($table)->whereDate('created_at', $today)->where('status', 'read')->count())
                ->description('Messages read by recipients')
                ->icon('heroicon-o-eye')
                ->color('primary'),
        ];
    }
}
