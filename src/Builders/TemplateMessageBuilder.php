<?php

namespace AmravatiSMS\LaravelWhatsApp\Builders;

use AmravatiSMS\LaravelWhatsApp\Client\WhatsAppClient;
use AmravatiSMS\LaravelWhatsApp\Responses\MessageResponse;

class TemplateMessageBuilder
{
    protected WhatsAppClient $client;
    protected string $name;
    protected string $phoneNumberId;
    protected ?string $to = null;
    protected string $language = 'en_US';
    protected array $bodyParams = [];
    protected array $headerParams = [];
    protected array $buttons = [];

    public function __construct(WhatsAppClient $client, string $name, string $phoneNumberId)
    {
        $this->client = $client;
        $this->name = $name;
        $this->phoneNumberId = $phoneNumberId;
    }

    public function to(string $to): self
    {
        $this->to = preg_replace('/[^0-9]/', '', $to);
        return $this;
    }

    public function language(string $language): self
    {
        $this->language = $language;
        return $this;
    }

    public function bodyParams(array $params): self
    {
        $this->bodyParams = $params;
        return $this;
    }

    public function headerText(string $text): self
    {
        $this->headerParams = [['type' => 'text', 'text' => $text]];
        return $this;
    }

    public function headerImage(string $urlOrId, bool $isId = false): self
    {
        $this->headerParams = [[
            'type' => 'image',
            $isId ? 'id' : 'url' => $urlOrId,
        ]];
        return $this;
    }

    public function headerVideo(string $urlOrId, bool $isId = false): self
    {
        $this->headerParams = [[
            'type' => 'video',
            $isId ? 'id' : 'url' => $urlOrId,
        ]];
        return $this;
    }

    public function headerLocation(float $latitude, float $longitude, string $name, string $address): self
    {
        $this->headerParams = [[
            'type' => 'location',
            'latitude' => $latitude,
            'longitude' => $longitude,
            'name' => $name,
            'address' => $address,
        ]];
        return $this;
    }

    public function buttonUrl(string $text): self
    {
        $this->buttons = [[
            'type' => 'button',
            'sub_type' => 'url',
            'text' => $text,
        ]];
        return $this;
    }

    public function buttonCatalog(string $thumbnailProductRetailerId): self
    {
        $this->buttons = [[
            'type' => 'button',
            'sub_type' => 'catalog',
            'thumbnail_product_retailer_id' => $thumbnailProductRetailerId,
        ]];
        return $this;
    }

    public function buttonFlow(): self
    {
        $this->buttons = [[
            'type' => 'button',
            'sub_type' => 'flow',
        ]];
        return $this;
    }

    public function buttons(array $buttons): self
    {
        $this->buttons = $buttons;
        return $this;
    }

    public function send(): MessageResponse
    {
        if (empty($this->to)) {
            throw new \InvalidArgumentException('Recipient phone number is required. Use ->to($phone) before sending.');
        }

        $payload = [
            'to' => $this->to,
            'phoneNoId' => $this->phoneNumberId,
            'type' => 'template',
            'name' => $this->name,
            'language' => $this->language,
        ];

        if (!empty($this->bodyParams)) {
            $payload['bodyParams'] = $this->bodyParams;
        }

        if (!empty($this->headerParams)) {
            $payload['headerParams'] = $this->headerParams;
        }

        if (!empty($this->buttons)) {
            $payload['buttons'] = $this->buttons;
        }

        return $this->client->sendRaw($payload);
    }

    public function toArray(): array
    {
        $payload = [
            'to' => $this->to,
            'phoneNoId' => $this->phoneNumberId,
            'type' => 'template',
            'name' => $this->name,
            'language' => $this->language,
        ];

        if (!empty($this->bodyParams)) {
            $payload['bodyParams'] = $this->bodyParams;
        }

        if (!empty($this->headerParams)) {
            $payload['headerParams'] = $this->headerParams;
        }

        if (!empty($this->buttons)) {
            $payload['buttons'] = $this->buttons;
        }

        return $payload;
    }
}
