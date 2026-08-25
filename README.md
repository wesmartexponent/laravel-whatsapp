# AmravatiSMS WhatsApp for Laravel

Official Laravel SDK for the [AmravatiSMS](https://amravatisms.com) WhatsApp Business API.

Send text, media and template messages, receive delivery webhooks, sync your approved
templates into your database, and manage everything from an optional Filament panel.

[![Tests](https://github.com/wesmartexponent/laravel-whatsapp/actions/workflows/tests.yml/badge.svg)](https://github.com/wesmartexponent/laravel-whatsapp/actions/workflows/tests.yml)

## Requirements

| | |
|---|---|
| PHP | 8.1+ |
| Laravel | 10.x, 11.x, 12.x |
| Filament *(optional)* | 3.x |

## Installation

```bash
composer require amravatisms/laravel-whatsapp
```

The service provider is auto-discovered. Publish the config file:

```bash
php artisan vendor:publish --tag=amravati-whatsapp-config
```

If you want the message-log and template tables, publish and run the migrations:

```bash
php artisan vendor:publish --tag=amravati-whatsapp-migrations
php artisan migrate
```

Or run the guided installer, which does both:

```bash
php artisan amravati:install
```

## Configuration

Add your credentials to `.env`:

```env
AMRAVATISMS_API_KEY=your_api_key
AMRAVATISMS_PHONE_NUMBER_ID=your_phone_number_id
AMRAVATISMS_BASE_URL=https://automate.amravatisms.com
```

Verify the connection by sending yourself a test message:

```bash
php artisan amravati:test 919876543210
```

All available options live in `config/amravati-whatsapp.php`:

| Key | Env | Default | Purpose |
|---|---|---|---|
| `base_url` | `AMRAVATISMS_BASE_URL` | `https://automate.amravatisms.com` | API host |
| `api_key` | `AMRAVATISMS_API_KEY` | — | Bearer token |
| `phone_number_id` | `AMRAVATISMS_PHONE_NUMBER_ID` | — | Default sender |
| `http.timeout` | `AMRAVATISMS_TIMEOUT` | `30` | Request timeout (seconds) |
| `http.retry_times` | `AMRAVATISMS_RETRY_TIMES` | `3` | Retry attempts on failure |
| `http.retry_sleep` | `AMRAVATISMS_RETRY_SLEEP` | `100` | Backoff between retries (ms) |
| `webhook.enabled` | `AMRAVATISMS_WEBHOOK_ENABLED` | `true` | Register the webhook route |
| `webhook.route` | `AMRAVATISMS_WEBHOOK_ROUTE` | `webhook/whatsapp` | Webhook URI |
| `webhook.verify_signature` | `AMRAVATISMS_VERIFY_SIGNATURE` | `true` | Enforce HMAC verification |
| `webhook.secret` | `AMRAVATISMS_WEBHOOK_SECRET` | — | HMAC signing secret |
| `queue.enabled` | `AMRAVATISMS_QUEUE_ENABLED` | `false` | Allow `->queue()` deferral |
| `queue.connection` | `AMRAVATISMS_QUEUE_CONNECTION` | — | Queue connection |
| `queue.queue` | `AMRAVATISMS_QUEUE_NAME` | `default` | Queue name |
| `logging.enabled` | `AMRAVATISMS_LOGGING` | `true` | Log outgoing messages |
| `logging.channel` | `AMRAVATISMS_LOG_CHANNEL` | `amravati-whatsapp` | Log channel |
| `default_language` | `AMRAVATISMS_DEFAULT_LANGUAGE` | `en_US` | Template language fallback |

## Sending messages

Use the `AmravatiSMS` facade, or inject `WhatsAppClient` if you prefer.

```php
use AmravatiSMS\LaravelWhatsApp\Facades\AmravatiSMS;

AmravatiSMS::sendText('919876543210', 'Hello from Laravel!');
```

Phone numbers are normalised for you — `+91 98765 43210`, `+91-98765-43210` and
`919876543210` are all equivalent.

### Media

```php
AmravatiSMS::sendImageByUrl('919876543210', 'https://example.com/photo.jpg', 'Nice pic');
AmravatiSMS::sendImageById('919876543210', 'media-id-123', 'Nice pic');
AmravatiSMS::sendVideoByUrl('919876543210', 'https://example.com/clip.mp4');
AmravatiSMS::sendDocument('919876543210', 'https://example.com/invoice.pdf', 'invoice.pdf', 'Your invoice');
AmravatiSMS::sendAudio('919876543210', 'https://example.com/note.ogg');
```

The `$caption` argument is optional on image, video and document sends.

### Templates

`AmravatiSMS::template()` returns a fluent builder. Call `->send()` to dispatch it.

```php
AmravatiSMS::template('order_status')
    ->to('919876543210')
    ->language('en_US')
    ->bodyParams(['John', 'Shipped', 'ORD-1234'])
    ->send();
```

Headers and buttons:

```php
AmravatiSMS::template('promo')
    ->to('919876543210')
    ->headerImage('https://example.com/promo.jpg')  // pass true as 2nd arg for a media ID
    ->bodyParams(['John', '50% OFF'])
    ->buttonUrl('track')
    ->send();
```

| Builder method | Effect |
|---|---|
| `to(string $phone)` | Recipient (required) |
| `language(string $code)` | Template language, defaults to `en_US` |
| `bodyParams(array $params)` | Positional `{{1}}`, `{{2}}`, … substitutions (replaces any set so far) |
| `bodyParam(string\|int\|float\|array $param)` | Append one body parameter, preserving order |
| `bodyCurrency(int $amount1000, string $code, string $fallback)` | Append a currency parameter |
| `bodyDateTime(string $fallback)` | Append a date/time parameter |
| `headerText(string $text)` | Text header |
| `headerImage(string $urlOrId, bool $isId = false)` | Image header |
| `headerVideo(string $urlOrId, bool $isId = false)` | Video header |
| `headerLocation(float $lat, float $lng, string $name, string $address)` | Location header |
| `buttonUrl(string $text)` | Dynamic URL button suffix |
| `buttonCatalog(string $thumbnailProductRetailerId)` | Catalog button |
| `buttonFlow()` | Flow button |
| `buttons(array $buttons)` | Raw button payload |
| `toArray()` | Inspect the payload without sending |

Calling `send()` without `to()` throws an `InvalidArgumentException`.

#### Typed body parameters

Currency and date/time placeholders take structured values rather than plain strings:

```php
AmravatiSMS::template('payment_receipt')
    ->to('919876543210')
    ->bodyParam('John Doe')
    ->bodyCurrency(99500, 'USD', '$99.50')   // amount_1000: 99500 renders as 99.50
    ->send();

AmravatiSMS::template('appointment_reminder')
    ->to('919876543210')
    ->bodyParam('John Doe')
    ->bodyDateTime('July 25, 2025 at 3:00 PM')
    ->send();
```

`bodyParam()` appends, so the call order is the placeholder order. Mixing it with
`bodyParams()` is fine as long as `bodyParams()` comes first — it replaces the whole set.

### Overriding the sender

```php
AmravatiSMS::withPhoneNumberId('9999999999')->sendText('919876543210', 'From another number');
```

`withPhoneNumberId()` and `queue()` both return a copy of the client, so an override
applies only to the chain you call it on — it never leaks into later sends.

### Queueing

Set `AMRAVATISMS_QUEUE_ENABLED=true`, then opt in per call:

```php
AmravatiSMS::queue()->sendText('919876543210', 'Sent from a worker');
```

The returned `MessageResponse` reports `isQueued() === true` and has no message ID yet.
When queueing is disabled, `->queue()` is a no-op and the message sends inline.

### Escape hatch

```php
AmravatiSMS::sendRaw([
    'to' => '919876543210',
    'phoneNoId' => config('amravati-whatsapp.phone_number_id'),
    'type' => 'text',
    'text' => 'Anything the API accepts',
]);
```

## Responses

Every send returns a `MessageResponse`:

```php
$response = AmravatiSMS::sendText('919876543210', 'Hi');

$response->isSuccess();       // bool
$response->isQueued();        // bool
$response->hasError();        // bool
$response->getErrorMessage(); // ?string
$response->messageId;         // ?string — e.g. "wamid.123456"
$response->status;            // ?string — e.g. "queued"
$response->raw;               // array — the untouched API payload
$response->toArray();
```

## Message status

```php
$status = AmravatiSMS::getStatus('wamid.123456');
$status->status; // "delivered"
```

```bash
php artisan amravati:status wamid.123456
```

## Error handling

Any non-2xx response raises a `WhatsAppException` after the configured retries are
exhausted:

```php
use AmravatiSMS\LaravelWhatsApp\Exceptions\WhatsAppException;

try {
    AmravatiSMS::sendText('919876543210', 'Hi');
} catch (WhatsAppException $e) {
    $e->getMessage();     // "Invalid API key"
    $e->getStatusCode();  // 401
    $e->getResponse();    // ?array — full error body
}
```

## Events

Register listeners in your `EventServiceProvider`:

| Event | Fired when |
|---|---|
| `Events\MessageSent` | API accepted the message |
| `Events\MessageFailed` | API rejected it, or the request threw |
| `Events\MessageDelivered` | Webhook reported `delivered` |
| `Events\WebhookReceived` | Any webhook payload arrives |

All live under `AmravatiSMS\LaravelWhatsApp\Events`.

## Webhooks

When `webhook.enabled` is true the package registers:

```
POST /webhook/whatsapp    → route name: amravati.whatsapp.webhook
```

Point your AmravatiSMS panel at that URL. Requests are verified with an HMAC-SHA256
digest of the raw request body, sent in the `X-Webhook-Signature` header and checked
against `webhook.secret`:

```env
AMRAVATISMS_WEBHOOK_SECRET=your_shared_secret
```

Verification is skipped if `webhook.verify_signature` is false **or** no secret is set.
Set both in production. A missing or mismatched signature returns `401`.

## Templates in your database

Pull your approved templates from the panel into the `whatsapp_templates` table:

```bash
php artisan amravati:templates:sync
```

The command paginates through the API and upserts on `name` + `language`, recording the
category, approval status, components, header type and parameter counts. Query them via
the `WhatsappTemplate` model.

## Artisan commands

| Command | Description |
|---|---|
| `amravati:install` | Publish config and migrations |
| `amravati:test {to?}` | Send a test message to verify configuration |
| `amravati:send:text {to} {message}` | Send a text message |
| `amravati:send:template {to} {template} {--params=} {--language=en_US}` | Send a template message |
| `amravati:status {messageId}` | Look up a message's status |
| `amravati:templates:sync` | Sync templates from the panel |

## Filament panel (optional)

Requires `filament/filament` ^3.0. Register the plugin on your panel:

```php
use AmravatiSMS\LaravelWhatsApp\Filament\WhatsAppPlugin;

public function panel(Panel $panel): Panel
{
    return $panel->plugins([
        WhatsAppPlugin::make(),
    ]);
}
```

You get two resources (templates, message logs), a Send Message and a Bulk Send page,
and three dashboard widgets (message stats, recent activity, template usage).

To send from any Filament table row, use the bundled action:

```php
use AmravatiSMS\LaravelWhatsApp\Filament\Actions\SendWhatsappAction;

$table->actions([
    SendWhatsappAction::make(),
]);
```

A template picker is available for your own forms:

```php
use AmravatiSMS\LaravelWhatsApp\Filament\Forms\Components\WhatsAppTemplateSelect;

WhatsAppTemplateSelect::make('template');
```

> **Note:** the Filament message-log resource and the three widgets read from the
> `whatsapp_message_logs` table, but the client currently only writes sends to the
> configured *log channel* — nothing inserts rows into that table yet. Until that is
> wired up, those views will be empty. See [Known limitations](#known-limitations).

## Database tables

`whatsapp_templates` — synced template metadata (name, language, category, status,
components, header type, body/header parameter counts, raw payload).

`whatsapp_message_logs` — intended message audit trail: `message_id`, `phone_number`,
`type`, `template_name`, `payload`, `response`, `status`, `success`, `error_message`,
`sent_at`, `delivered_at`, `read_at`.

Both table names are configurable (`templates.table`, `logging.table`).

## API endpoints

Every call is a `Bearer`-authenticated JSON request against your panel host:

| Method | Path | Used by |
|---|---|---|
| `POST` | `/v2/whatsapp-business/messages` | all send methods, `sendRaw()` |
| `GET` | `/v2/whatsapp-business/status/{messageId}` | `getStatus()` |
| `GET` | `/v2/whatsapp-business/templates` | `getTemplates()`, `amravati:templates:sync` |

The first two are pinned to the published *WhatsApp Messaging API* Postman collection by
[`tests/Feature/PostmanContractTest.php`](tests/Feature/PostmanContractTest.php), which
asserts the exact URL and JSON body for each documented request. If the API contract
shifts, those tests fail first.

## Testing

The HTTP layer goes through Laravel's `Http` facade, so `Http::fake()` works as usual:

```php
use Illuminate\Support\Facades\Http;

Http::fake([
    '*' => Http::response(['success' => true, 'message_id' => 'wamid.test'], 200),
]);

$response = AmravatiSMS::sendText('919876543210', 'Hi');

Http::assertSent(fn ($request) => $request['type'] === 'text');
```

To run the package's own suite:

```bash
composer install
vendor/bin/phpunit
```

## Known limitations

- **Message logs are never written.** `whatsapp_message_logs` is read by the webhook
  handler and the Filament widgets, but no code inserts into it. The Filament message-log
  resource also resolves `App\Models\WhatsappMessageLog`, a class this package does not
  ship.
- **`getTemplates()` is not in the published Postman collection.** The collection documents
  only `POST /v2/whatsapp-business/messages` and `GET /v2/whatsapp-business/status/{id}`.
  The templates path follows the same `/v2/whatsapp-business/` prefix, but it is inferred
  rather than documented — confirm it against your panel before relying on
  `amravati:templates:sync`.
- **Response shapes are unverified.** The Postman collection ships no example responses, so
  `MessageResponse`'s field mapping is based on observed keys (`success`, `message_id`,
  `status`). In particular, when a response omits `success`, the class falls back to the
  `status` string, which coerces any non-empty value — including `"failed"` — to `true`.
- **Tests use PHPUnit's deprecated doc-comment `@test` annotations.** They still run on
  PHPUnit 11 but emit deprecation notices and will not be discovered on PHPUnit 12.
  Migrating to the `#[Test]` attribute is recommended before widening the PHPUnit
  constraint.

## Security

Never commit your API key or webhook secret. Keep them in `.env`, and keep production
values on the production host only. If you find a security issue, email
[support@amravatisms.com](mailto:support@amravatisms.com) rather than opening a public
issue.

## License

MIT — see [LICENSE](LICENSE).
