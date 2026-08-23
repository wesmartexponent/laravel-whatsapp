<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Bulk WhatsApp Send
        </x-slot>

        <x-slot name="description">
            Upload a CSV file to send messages to multiple recipients at once.
        </x-slot>

        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 mb-6">
            <h4 class="font-medium text-blue-900 dark:text-blue-100 mb-2">CSV Format</h4>
            <p class="text-sm text-blue-800 dark:text-blue-200">
                Your CSV must contain a <code>phone</code> column. Add additional columns for template parameters.
            </p>
            <pre class="mt-2 text-xs bg-white dark:bg-gray-800 rounded p-2 overflow-x-auto">phone,name,status,order_id
919876543210,John,Shipped,ORD-001
919876543211,Jane,Delivered,ORD-002</pre>
        </div>

        <form wire:submit="sendBulk" class="space-y-6">
            {{ $this->form }}

            <div class="flex items-center gap-3">
                <x-filament::button type="submit" icon="heroicon-m-users">
                    Start Bulk Send
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>

    @if(isset($this->data['template']) && !empty($this->data['template']))
        @php
            $template = \AmravatiSMS\LaravelWhatsApp\Models\WhatsappTemplate::findByName($this->data['template']);
        @endphp

        @if($template)
            <x-filament::section class="mt-6">
                <x-slot name="heading">
                    Selected Template: {{ $template->name }}
                </x-slot>

                <div class="space-y-2">
                    <p class="text-sm"><strong>Language:</strong> {{ $template->language }}</p>
                    <p class="text-sm"><strong>Category:</strong> {{ $template->category }}</p>
                    <p class="text-sm"><strong>Body Parameters:</strong> {{ $template->body_params_count }}</p>
                    <p class="text-sm"><strong>Header Type:</strong> {{ $template->header_type ?? 'None' }}</p>
                </div>

                <div class="mt-4 bg-gray-50 dark:bg-gray-900 rounded-lg p-3 font-mono text-sm">
                    {!! nl2br(e($template->preview())) !!}
                </div>
            </x-filament::section>
        @endif
    @endif
</x-filament-panels::page>
