<?php

namespace AmravatiSMS\LaravelWhatsApp\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TemplateUsageWidget extends ChartWidget
{
    protected static ?string $heading = 'Template Usage (Last 7 Days)';
    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $table = config('amravati-whatsapp.logging.table', 'whatsapp_message_logs');

        $data = DB::table($table)
            ->whereNotNull('template_name')
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('template_name, COUNT(*) as count')
            ->groupBy('template_name')
            ->orderByDesc('count')
            ->limit(5)
            ->pluck('count', 'template_name')
            ->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Messages Sent',
                    'data' => array_values($data),
                    'backgroundColor' => [
                        '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
                    ],
                ],
            ],
            'labels' => array_keys($data),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
