<?php

namespace AmravatiSMS\LaravelWhatsApp\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use AmravatiSMS\LaravelWhatsApp\Events\WebhookReceived;
use AmravatiSMS\LaravelWhatsApp\Events\MessageDelivered;
use AmravatiSMS\LaravelWhatsApp\Events\MessageFailed;

class WebhookController
{
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        event(new WebhookReceived($request, $payload));

        $status = $payload['status'] ?? null;
        $messageId = $payload['message_id'] ?? $payload['id'] ?? null;

        if ($status === 'delivered') {
            event(new MessageDelivered($payload));
        }

        if ($status === 'failed') {
            event(new MessageFailed($payload));
        }

        // Update message log if exists
        if ($messageId && config('amravati-whatsapp.logging.enabled')) {
            $table = config('amravati-whatsapp.logging.table', 'whatsapp_message_logs');
            \DB::table($table)
                ->where('message_id', $messageId)
                ->update([
                    'status' => $status,
                    'delivered_at' => $status === 'delivered' ? now() : null,
                    'read_at' => $status === 'read' ? now() : null,
                    'updated_at' => now(),
                ]);
        }

        return response()->json(['success' => true]);
    }
}
