<?php

namespace AmravatiSMS\LaravelWhatsApp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('amravati-whatsapp.webhook.verify_signature')) {
            return $next($request);
        }

        $secret = config('amravati-whatsapp.webhook.secret');

        if (empty($secret)) {
            return $next($request);
        }

        $signature = $request->header('X-Webhook-Signature');

        if (! $signature) {
            return response()->json(['error' => 'Missing signature'], 401);
        }

        $computed = hash_hmac('sha256', $request->getContent(), $secret);

        if (! hash_equals($computed, $signature)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        return $next($request);
    }
}
