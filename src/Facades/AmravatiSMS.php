<?php

namespace AmravatiSMS\LaravelWhatsApp\Facades;

use Illuminate\Support\Facades\Facade;
use AmravatiSMS\LaravelWhatsApp\Builders\TemplateMessageBuilder;
use AmravatiSMS\LaravelWhatsApp\Responses\MessageResponse;

/**
 * @method static \AmravatiSMS\LaravelWhatsApp\Client\WhatsAppClient queue()
 * @method static MessageResponse sendText(string $to, string $text)
 * @method static MessageResponse sendImageByUrl(string $to, string $url, ?string $caption = null)
 * @method static MessageResponse sendImageById(string $to, string $mediaId, ?string $caption = null)
 * @method static MessageResponse sendVideoByUrl(string $to, string $url, ?string $caption = null)
 * @method static MessageResponse sendDocument(string $to, string $url, string $filename, ?string $caption = null)
 * @method static MessageResponse sendAudio(string $to, string $url)
 * @method static TemplateMessageBuilder template(string $name)
 * @method static MessageResponse getStatus(string $messageId)
 * @method static array getTemplates()
 * @method static \AmravatiSMS\LaravelWhatsApp\Client\WhatsAppClient withPhoneNumberId(string $phoneNumberId)
 *
 * @see \AmravatiSMS\LaravelWhatsApp\Client\WhatsAppClient
 */
class AmravatiSMS extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return WhatsAppClient::class;
    }
}
