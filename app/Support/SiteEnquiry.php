<?php

namespace App\Support;

use App\Models\Enquiry;
use App\Models\LegalDocument;

class SiteEnquiry
{
    /**
     * @param  array<string, mixed>  $validated
     * @return array{enquiry: Enquiry, duplicate: bool}
     */
    public function storeTransfer(array $validated): array
    {
        $contact = is_array($validated['contact'] ?? null) ? $validated['contact'] : [];
        $phone = $this->text($contact['phone'] ?? null);
        $email = $this->text($contact['email'] ?? null);
        $messenger = $this->messengerLabel($contact['messenger'] ?? null);

        if ($phone === null && ($contact['method'] ?? null) !== 'email') {
            $phone = $this->text($contact['value'] ?? null);
        }

        if ($email === null && ($contact['method'] ?? null) === 'email') {
            $email = $this->text($contact['value'] ?? null);
        }

        $payload = $validated;
        unset($payload['contact']['personal_data_consent']);

        if ($messenger !== null) {
            $payload['contact']['messenger'] = $messenger;
        }

        return $this->store(
            'transfer',
            $this->text($contact['name'] ?? null) ?? '',
            $phone,
            $email,
            $this->text($contact['comment'] ?? null),
            $payload,
            $this->fingerprint([
                'transfer',
                $validated['trip'] ?? '',
                $contact['name'] ?? '',
                $phone ?? '',
                $email ?? '',
                $messenger ?? '',
                $validated['outbound']['pickup']['formatted_address'] ?? '',
                $validated['outbound']['dropoff']['formatted_address'] ?? '',
                $validated['outbound']['date'] ?? '',
                $validated['outbound']['time'] ?? '',
                $contact['comment'] ?? '',
            ]),
            $messenger,
        );
    }

    /**
     * @return array{enquiry: Enquiry, duplicate: bool}
     */
    public function storeContact(string $name, string $contact, string $message): array
    {
        $email = filter_var($contact, FILTER_VALIDATE_EMAIL) ? $contact : null;
        $phone = $email === null ? $contact : null;

        return $this->store(
            'contact',
            $name,
            $phone,
            $email,
            $message,
            ['message' => $message],
            $this->fingerprint(['contact', $name, $contact, $message]),
            null,
        );
    }

    /**
     * @param  array{from: string, to: string, date: string, email: string, destination: string}  $data
     * @return array{enquiry: Enquiry, duplicate: bool}
     */
    public function storeDestination(array $data): array
    {
        return $this->store(
            'destination',
            $data['email'],
            null,
            $data['email'],
            null,
            [
                'destination' => $data['destination'],
                'from' => $data['from'],
                'to' => $data['to'],
                'date' => $data['date'],
            ],
            $this->fingerprint(['destination', $data['destination'], $data['email'], $data['from'], $data['to'], $data['date']]),
            null,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{enquiry: Enquiry, duplicate: bool}
     */
    private function store(
        string $kind,
        string $name,
        ?string $phone,
        ?string $email,
        ?string $comment,
        array $payload,
        string $fingerprint,
        ?string $messenger = null,
    ): array {
        $existing = Enquiry::query()
            ->where('fingerprint', $fingerprint)
            ->where('created_at', '>=', now()->subMinutes(10))
            ->first();

        if ($existing instanceof Enquiry) {
            return ['enquiry' => $existing, 'duplicate' => true];
        }

        $version = LegalDocument::consentVersion();

        $enquiry = Enquiry::query()->create([
            'kind' => $kind,
            'status' => 'new',
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'messenger' => $messenger,
            'comment' => $comment,
            'payload' => $payload,
            'consent_version_id' => $version?->id,
            'consented_at' => now(),
            'fingerprint' => $fingerprint,
        ]);

        return ['enquiry' => $enquiry, 'duplicate' => false];
    }

    /**
     * @param  list<mixed>  $parts
     */
    private function fingerprint(array $parts): string
    {
        $normalized = array_map(
            fn (mixed $part): string => mb_strtolower(trim((string) $part)),
            $parts,
        );

        return hash('sha256', implode('|', $normalized));
    }

    private function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function messengerLabel(mixed $value): ?string
    {
        $messenger = $this->text(is_scalar($value) ? (string) $value : null);

        if ($messenger === null) {
            return null;
        }

        return match (mb_strtolower($messenger)) {
            'whatsapp' => 'WhatsApp',
            'telegram' => 'Telegram',
            default => null,
        };
    }
}
