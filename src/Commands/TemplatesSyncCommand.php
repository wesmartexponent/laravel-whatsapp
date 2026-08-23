<?php

namespace AmravatiSMS\LaravelWhatsApp\Commands;

use Illuminate\Console\Command;
use AmravatiSMS\LaravelWhatsApp\Facades\AmravatiSMS;
use AmravatiSMS\LaravelWhatsApp\Models\WhatsappTemplate;

class TemplatesSyncCommand extends Command
{
    protected $signature = 'amravati:templates:sync';
    protected $description = 'Sync WhatsApp templates from AmravatiSMS panel';

    public function handle(): int
    {
        $this->info('Syncing WhatsApp templates...');

        $cursor = null;
        $imported = 0;
        $updated = 0;

        do {
            $response = AmravatiSMS::getTemplates(limit: 100, after: $cursor);
            $templates = $response['data'] ?? [];
            $cursor = $response['paging']['next_cursor'] ?? null;

            foreach ($templates as $template) {
                $model = WhatsappTemplate::updateOrCreate(
                    [
                        'name' => $template['name'],
                        'language' => $template['language'] ?? 'en_US',
                    ],
                    [
                        'category' => $template['category'] ?? null,
                        'status' => $template['status'] ?? 'UNKNOWN',
                        'components' => $template['components'] ?? [],
                        'header_type' => $this->detectHeaderType($template['components'] ?? []),
                        'body_params_count' => $this->countBodyParams($template['components'] ?? []),
                        'header_params_count' => $this->countHeaderParams($template['components'] ?? []),
                        'raw' => $template,
                    ]
                );

                if ($model->wasRecentlyCreated) {
                    $imported++;
                } else {
                    $updated++;
                }
            }

            $this->info("Processed batch... (imported: {$imported}, updated: {$updated})");
        } while ($cursor);

        $this->newLine();
        $this->info("Sync complete! Imported: {$imported}, Updated: {$updated}");

        return self::SUCCESS;
    }

    protected function detectHeaderType(array $components): ?string
    {
        foreach ($components as $component) {
            if (($component['type'] ?? '') === 'HEADER') {
                return $component['format'] ?? 'TEXT';
            }
        }
        return null;
    }

    protected function countBodyParams(array $components): int
    {
        foreach ($components as $component) {
            if (($component['type'] ?? '') === 'BODY') {
                $text = $component['text'] ?? '';
                preg_match_all('/\{\{(\d+)\}\}/', $text, $matches);
                return count($matches[0] ?? []);
            }
        }
        return 0;
    }

    protected function countHeaderParams(array $components): int
    {
        foreach ($components as $component) {
            if (($component['type'] ?? '') === 'HEADER') {
                $text = $component['text'] ?? '';
                preg_match_all('/\{\{(\d+)\}\}/', $text, $matches);
                return count($matches[0] ?? []);
            }
        }
        return 0;
    }
}
