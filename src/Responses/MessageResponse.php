<?php

namespace AmravatiSMS\LaravelWhatsApp\Responses;

class MessageResponse
{
    public bool $success;
    public ?string $messageId;
    public ?string $status;
    public ?string $message;
    public bool $queued;
    public ?array $error;
    public array $raw;

    public function __construct(array $data)
    {
        $this->raw = $data;
        $this->success = $data['success'] ?? ($data['status'] ?? false);
        $this->messageId = $data['message_id'] ?? $data['id'] ?? null;
        $this->status = $data['status'] ?? null;
        $this->message = $data['message'] ?? null;
        $this->queued = $data['queued'] ?? false;
        $this->error = $data['error'] ?? null;
    }

    public function isSuccess(): bool
    {
        return $this->success === true;
    }

    public function isQueued(): bool
    {
        return $this->queued;
    }

    public function hasError(): bool
    {
        return $this->error !== null || $this->success === false;
    }

    public function getErrorMessage(): ?string
    {
        return $this->error['message'] ?? $this->message ?? null;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message_id' => $this->messageId,
            'status' => $this->status,
            'message' => $this->message,
            'queued' => $this->queued,
            'error' => $this->error,
            'raw' => $this->raw,
        ];
    }
}
