<?php

namespace AmravatiSMS\LaravelWhatsApp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;

class WhatsappTemplate extends Model
{
    protected $table;

    protected $fillable = [
        'name',
        'language',
        'category',
        'status',
        'components',
        'header_type',
        'body_params_count',
        'header_params_count',
        'raw',
    ];

    protected $casts = [
        'components' => 'array',
        'raw' => 'array',
        'body_params_count' => 'integer',
        'header_params_count' => 'integer',
    ];

    public function __construct(array $attributes = [])
    {
        $this->table = config('amravati-whatsapp.templates.table', 'whatsapp_templates');
        parent::__construct($attributes);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'APPROVED');
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByLanguage($query, string $language)
    {
        return $query->where('language', $language);
    }

    public function isApproved(): bool
    {
        return strtoupper($this->status) === 'APPROVED';
    }

    public function isPending(): bool
    {
        return strtoupper($this->status) === 'PENDING';
    }

    public function isRejected(): bool
    {
        return strtoupper($this->status) === 'REJECTED';
    }

    public function requiredBodyParams(): array
    {
        $params = [];
        foreach ($this->components ?? [] as $component) {
            if (($component['type'] ?? '') === 'BODY') {
                $text = $component['text'] ?? '';
                preg_match_all('/\{\{(\d+)\}\}/', $text, $matches);
                foreach ($matches[1] as $index => $number) {
                    $params[$number] = "Parameter {$number}";
                }
            }
        }
        return $params;
    }

    public function hasHeader(): bool
    {
        return $this->header_type !== null;
    }

    public function hasHeaderImage(): bool
    {
        return $this->header_type === 'IMAGE';
    }

    public function hasHeaderVideo(): bool
    {
        return $this->header_type === 'VIDEO';
    }

    public function hasHeaderText(): bool
    {
        return $this->header_type === 'TEXT';
    }

    public function preview(): string
    {
        $parts = [];
        foreach ($this->components ?? [] as $component) {
            $type = $component['type'] ?? '';
            if ($type === 'HEADER') {
                $parts[] = "**" . ($component['text'] ?? '[Header]') . "**";
            } elseif ($type === 'BODY') {
                $parts[] = $component['text'] ?? '[Body]';
            } elseif ($type === 'FOOTER') {
                $parts[] = "_" . ($component['text'] ?? '[Footer]') . "_";
            }
        }
        return implode("\n\n", $parts);
    }

    public function validateParams(array $params): bool
    {
        $required = $this->requiredBodyParams();
        $provided = count($params);
        $needed = count($required);

        if ($provided < $needed) {
            throw new \InvalidArgumentException("Template '{$this->name}' requires {$needed} body parameters, {$provided} provided.");
        }

        return true;
    }

    public static function findByName(string $name, ?string $language = null): ?self
    {
        $query = static::where('name', $name);
        if ($language) {
            $query->where('language', $language);
        }
        return $query->first();
    }
}
