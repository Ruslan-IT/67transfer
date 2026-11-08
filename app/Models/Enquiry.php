<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enquiry extends Model
{
    public const STATUSES = [
        'new' => 'Новая',
        'processing' => 'В работе',
        'done' => 'Завершена',
        'cancelled' => 'Отменена',
    ];

    public const KINDS = [
        'transfer' => 'Трансфер',
        'contact' => 'Вопрос',
        'destination' => 'Направление',
    ];

    public const MESSENGERS = [
        'WhatsApp' => 'WhatsApp',
        'Telegram' => 'Telegram',
    ];

    protected $fillable = [
        'kind',
        'status',
        'name',
        'phone',
        'email',
        'messenger',
        'comment',
        'payload',
        'consent_version_id',
        'consented_at',
        'fingerprint',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'consented_at' => 'datetime',
        ];
    }

    public function consentVersion(): BelongsTo
    {
        return $this->belongsTo(LegalDocumentVersion::class, 'consent_version_id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? $this->kind;
    }

    public function detailsText(): string
    {
        $payload = is_array($this->payload) ? $this->payload : [];

        if ($this->kind === 'contact') {
            return trim((string) ($payload['message'] ?? ''));
        }

        if ($this->kind === 'destination') {
            return trim(implode("\n", array_filter([
                filled($payload['destination'] ?? null) ? 'Направление: '.$payload['destination'] : null,
                filled($payload['from'] ?? null) ? 'Откуда: '.$payload['from'] : null,
                filled($payload['to'] ?? null) ? 'Куда: '.$payload['to'] : null,
                filled($payload['date'] ?? null) ? 'Дата: '.$payload['date'] : null,
            ])));
        }

        $lines = [];
        $trip = ($payload['trip'] ?? null) === 'return' ? 'Туда и обратно' : 'В одну сторону';
        $lines[] = 'Тип: '.$trip;

        foreach (['outbound' => 'Туда', 'return' => 'Обратно'] as $leg => $title) {
            $part = $payload[$leg] ?? null;

            if (! is_array($part)) {
                continue;
            }

            $from = $part['pickup']['formatted_address'] ?? '';
            $to = $part['dropoff']['formatted_address'] ?? '';
            $lines[] = $title.': '.$from.' → '.$to;
            $lines[] = 'Дата и время: '.trim(($part['date'] ?? '').' '.($part['time'] ?? ''));
            $people = $part['passengers'] ?? [];

            if (is_array($people)) {
                $count = (int) ($people['adults'] ?? 0)
                    + count(is_array($people['children'] ?? null) ? $people['children'] : [])
                    + count(is_array($people['infants'] ?? null) ? $people['infants'] : []);
                $lines[] = 'Пассажиров: '.$count;
            }
        }

        $wishes = $payload['wishes'] ?? [];

        if (is_array($wishes) && ! empty($wishes['pet'])) {
            $lines[] = 'Питомец: '.trim((string) ($wishes['pet_details'] ?? ''));
        }

        $contact = is_array($payload['contact'] ?? null) ? $payload['contact'] : [];
        $messenger = $this->messenger ?: ($contact['messenger'] ?? null);

        if (filled($messenger)) {
            $lines[] = 'Мессенджер: '.$messenger;
        }

        if (filled($contact['flight_number'] ?? null)) {
            $lines[] = 'Рейс: '.$contact['flight_number'];
        }

        if (filled($contact['return_flight_number'] ?? null)) {
            $lines[] = 'Обратный рейс: '.$contact['return_flight_number'];
        }

        return trim(implode("\n", $lines));
    }
}
