<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Send WhatsApp Message
        </x-slot>

        <x-slot name="description">
            Send text, media, or template messages directly from your admin panel.
        </x-slot>

        <form wire:submit="send" class="space-y-6">
            {{ $this->form }}

            <div class="flex items-center gap-3">
                <x-filament::button type="submit" icon="heroicon-m-paper-airplane">
                    Send Message
                </x-filament::button>

                <x-filament::button
                    type="button"
                    color="gray"
                    icon="heroicon-m-arrow-path"
                    wire:click="$refresh"
                >
                    Reset
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>

    @if($this->data['message_type'] === 'template' && !empty($this->data['template']))
        @php
            $template = \AmravatiSMS\LaravelWhatsApp\Models\WhatsappTemplate::findByName($this->data['template']);
        @endphp

        @if($template)
            <x-filament::section class="mt-6">
                <x-slot name="heading">
                    Template Preview: {{ $template->name }}
                </x-slot>

                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 font-mono text-sm whitespace-pre-wrap">
                    {!! nl2br(e($template->preview())) !!}
                </div>

                <div class="mt-4 flex gap-2">
                    <x-filament::badge :color="$template->isApproved() ? 'success' : 'warning'">
                        {{ $template->status }}
                    </x-filament::badge>
                    <x-filament::badge>
                        {{ $template->language }}
                    </x-filament::badge>
                    <x-filament::badge color="info">
                        {{ $template->category }}
                    </x-filament::badge>
                </div>

                @if($template->body_params_count > 0)
                    <div class="mt-4">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Required Parameters: {{ $template->body_params_count }}
                        </p>
                    </div>
                @endif
            </x-filament::section>
        @endif
    @endif
</x-filament-panels::page>
