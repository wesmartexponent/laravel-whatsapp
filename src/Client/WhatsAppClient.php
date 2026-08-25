<?php

namespace AmravatiSMS\LaravelWhatsApp\Client;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use AmravatiSMS\LaravelWhatsApp\Builders\TemplateMessageBuilder;
use AmravatiSMS\LaravelWhatsApp\Responses\MessageResponse;
use AmravatiSMS\LaravelWhatsApp\Events\MessageSent;
use AmravatiSMS\LaravelWhatsApp\Events\MessageFailed;
use AmravatiSMS\LaravelWhatsApp\Exceptions\WhatsAppException;

class WhatsAppClient
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $phoneNumberId;
    protected array $httpConfig;
    protected bool $queued = false;

    public function __construct(string $baseUrl, string $apiKey, string $phoneNumberId, array $httpConfig = [])
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
        $this->phoneNumberId = $phoneNumberId;
        $this->httpConfig = $httpConfig;
    }

    /**
     * Enable queue mode for subsequent calls.
     *
     * Returns a copy so the container-shared instance is left untouched.
     */
    public function queue(): self
    {
        $clone = clone $this;
        $clone->queued = true;

        return $clone;
    }

    /**
     * Use a different phone number ID for this call.
     *
     * Returns a copy so the container-shared instance is left untouched.
     */
    public function withPhoneNumberId(string $phoneNumberId): self
    {
        $clone = clone $this;
        $clone->phoneNumberId = $phoneNumberId;

        return $clone;
    }

    /**
     * Send a plain text message.
     */
    public function sendText(string $to, string $text): MessageResponse
    {
        return $this->send('text', [
            'to' => $this->normalizePhone($to),
            'phoneNoId' => $this->phoneNumberId,
            'type' => 'text',
            'text' => $text,
        ]);
    }

    /**
     * Send an image message by URL.
     */
    public function sendImageByUrl(string $to, string $url, ?string $caption = null): MessageResponse
    {
        $payload = [
            'to' => $this->normalizePhone($to),
            'phoneNoId' => $this->phoneNumberId,
            'type' => 'image',
            'url' => $url,
        ];

        if ($caption !== null) {
            $payload['caption'] = $caption;
        }

        return $this->send('image', $payload);
    }

    /**
     * Send an image message by media ID.
     */
    public function sendImageById(string $to, string $mediaId, ?string $caption = null): MessageResponse
    {
        $payload = [
            'to' => $this->normalizePhone($to),
            'phoneNoId' => $this->phoneNumberId,
            'type' => 'image',
            'id' => $mediaId,
        ];

        if ($caption !== null) {
            $payload['caption'] = $caption;
        }

        return $this->send('image', $payload);
    }

    /**
     * Send a video message by URL.
     */
    public function sendVideoByUrl(string $to, string $url, ?string $caption = null): MessageResponse
    {
        $payload = [
            'to' => $this->normalizePhone($to),
            'phoneNoId' => $this->phoneNumberId,
            'type' => 'video',
            'url' => $url,
        ];

        if ($caption !== null) {
            $payload['caption'] = $caption;
        }

        return $this->send('video', $payload);
    }

    /**
     * Send a document message.
     */
    public function sendDocument(string $to, string $url, string $filename, ?string $caption = null): MessageResponse
    {
        $payload = [
            'to' => $this->normalizePhone($to),
            'phoneNoId' => $this->phoneNumberId,
            'type' => 'document',
            'url' => $url,
            'filename' => $filename,
        ];

        if ($caption !== null) {
            $payload['caption'] = $caption;
        }

        return $this->send('document', $payload);
    }

    /**
     * Send an audio message.
     */
    public function sendAudio(string $to, string $url): MessageResponse
    {
        return $this->send('audio', [
            'to' => $this->normalizePhone($to),
            'phoneNoId' => $this->phoneNumberId,
            'type' => 'audio',
            'url' => $url,
        ]);
    }

    /**
     * Create a template message builder.
     */
    public function template(string $name): TemplateMessageBuilder
    {
        return new TemplateMessageBuilder($this, $name, $this->phoneNumberId);
    }

    /**
     * Get message status by ID.
     */
    public function getStatus(string $messageId): MessageResponse
    {
        $response = $this->request('GET', "/v2/whatsapp-business/status/{$messageId}");

        return new MessageResponse($response);
    }

    /**
     * Get all approved templates from the API.
     */
    public function getTemplates(int $limit = 100, ?string $after = null): array
    {
        $params = ['limit' => $limit];

        if ($after) {
            $params['after'] = $after;
        }

        return $this->request('GET', '/v2/whatsapp-business/templates', $params);
    }

    /**
     * Send a raw payload to the messages endpoint.
     */
    public function sendRaw(array $payload): MessageResponse
    {
        return $this->send('raw', $payload);
    }

    /**
     * Internal send method.
     */
    protected function send(string $type, array $payload): MessageResponse
    {
        if ($this->queued && config('amravati-whatsapp.queue.enabled')) {
            dispatch(function () use ($type, $payload) {
                $this->executeSend($type, $payload);
            })->onConnection(config('amravati-whatsapp.queue.connection'))
              ->onQueue(config('amravati-whatsapp.queue.queue'));

            return new MessageResponse([
                'success' => true,
                'queued' => true,
                'message' => 'Message queued for delivery.',
            ]);
        }

        return $this->executeSend($type, $payload);
    }

    protected function executeSend(string $type, array $payload): MessageResponse
    {
        try {
            $response = $this->request('POST', '/v2/whatsapp-business/messages', $payload);

            $messageResponse = new MessageResponse($response);

            if ($messageResponse->success) {
                event(new MessageSent($payload, $messageResponse));
            } else {
                event(new MessageFailed($payload, $messageResponse));
            }

            $this->logMessage($type, $payload, $messageResponse);

            return $messageResponse;
        } catch (\Throwable $e) {
            event(new MessageFailed($payload, null, $e));
            throw $e;
        }
    }

    /**
     * Make an HTTP request to the API.
     */
    public function request(string $method, string $endpoint, array $data = []): array
    {
        $url = $this->baseUrl . $endpoint;

        $http = Http::withToken($this->apiKey)
            ->timeout($this->httpConfig['timeout'] ?? 30)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]);

        $retryTimes = $this->httpConfig['retry_times'] ?? 3;
        $retrySleep = $this->httpConfig['retry_sleep'] ?? 100;

        $http = $http->retry($retryTimes, $retrySleep, throw: false);

        $response = strtoupper($method) === 'GET'
            ? $http->get($url, $data)
            : $http->post($url, $data);

        if ($response->failed()) {
            throw new WhatsAppException(
                message: $response->json('message') ?? 'API request failed.',
                statusCode: $response->status(),
                response: $response->json(),
            );
        }

        return $response->json();
    }

    /**
     * Normalize phone number (remove + prefix if present, ensure E.164).
     */
    protected function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        return $phone;
    }

    /**
     * Log the message if logging is enabled.
     */
    protected function logMessage(string $type, array $payload, MessageResponse $response): void
    {
        if (! config('amravati-whatsapp.logging.enabled')) {
            return;
        }

        $channel = config('amravati-whatsapp.logging.channel');

        Log::channel($channel)->info('WhatsApp message sent', [
            'type' => $type,
            'to' => $payload['to'] ?? null,
            'message_id' => $response->messageId,
            'status' => $response->status,
            'success' => $response->success,
        ]);
    }
}
