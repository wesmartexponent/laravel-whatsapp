<?php

namespace AmravatiSMS\LaravelWhatsApp\Exceptions;

use Exception;

class WhatsAppException extends Exception
{
    public ?array $response;
    public ?int $statusCode;

    public function __construct(string $message, ?int $statusCode = null, ?array $response = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->statusCode = $statusCode;
        $this->response = $response;
    }

    public function getResponse(): ?array
    {
        return $this->response;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }
}
