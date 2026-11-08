<?php

namespace App\Support;

use App\Models\HomeBlock;
use Illuminate\Support\Collection;
use Throwable;

class HomeBlocks
{
    /**
     * @var Collection<int, array<string, mixed>>|null
     */
    private ?Collection $visible = null;

    /**
     * Enabled sections for the homepage, with safe defaults when the table is empty or unavailable.
     *
     * @return Collection<int, array{
     *     type: string,
     *     partial: string,
     *     anchor: ?string,
     *     nav_label: ?string,
     *     payload: array<string, mixed>
     * }>
     */
    public function visible(): Collection
    {
        if ($this->visible instanceof Collection) {
            return $this->visible;
        }

        try {
            $records = HomeBlock::query()->ordered()->get();
        } catch (Throwable $exception) {
            report($exception);

            return $this->visible = $this->fallbackSections();
        }

        if ($records->isEmpty()) {
            return $this->visible = $this->fallbackSections();
        }

        $usedAnchors = [];
        $sections = collect();

        foreach ($records as $record) {
            if (! $record->is_enabled) {
                continue;
            }

            $section = $this->present($record, $usedAnchors);

            if ($section === null) {
                continue;
            }

            if (filled($section['anchor'])) {
                $usedAnchors[$section['anchor']] = true;
            }

            $sections->push($section);
        }

        return $this->visible = $sections->values();
    }

    /**
     * @return list<array{href: string, label: string}>
     */
    public function navItems(): array
    {
        $items = [];

        foreach ($this->visible() as $section) {
            $anchor = $section['anchor'] ?? null;
            $label = $section['nav_label'] ?? null;

            if (! is_string($anchor) || $anchor === '' || ! is_string($label) || $label === '') {
                continue;
            }

            $items[] = [
                'href' => '#'.$anchor,
                'label' => $label,
            ];
        }

        return $items;
    }

    /**
     * Create the four starter blocks if they are missing.
     */
    public function ensureSystemBlocks(): void
    {
        try {
            foreach (HomeBlockRegistry::systemBlueprints() as $blueprint) {
                $exists = HomeBlock::query()
                    ->where('type', $blueprint['type'])
                    ->where('is_system', true)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $maxOrder = (int) HomeBlock::query()->max('sort_order');

                HomeBlock::query()->create([
                    'type' => $blueprint['type'],
                    'name' => $blueprint['name'],
                    'is_enabled' => true,
                    'is_system' => true,
                    'sort_order' => $blueprint['sort_order'] ?? ($maxOrder + 1),
                    'payload' => $blueprint['payload'],
                ]);
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /**
     * @param  array<string, bool>  $usedAnchors
     * @return array{
     *     type: string,
     *     partial: string,
     *     anchor: ?string,
     *     nav_label: ?string,
     *     payload: array<string, mixed>
     * }|null
     */
    private function present(HomeBlock $record, array $usedAnchors): ?array
    {
        $type = (string) $record->type;

        if (! HomeBlockRegistry::isKnown($type)) {
            return null;
        }

        $partial = HomeBlockRegistry::partial($type);

        if (! is_string($partial) || $partial === '') {
            return null;
        }

        $anchor = HomeBlockRegistry::anchor($type);

        if (is_string($anchor) && $anchor !== '' && isset($usedAnchors[$anchor])) {
            $anchor = $anchor.'-'.$record->id;
        }

        try {
            $payload = HomeBlockRegistry::mergePayload($type, $record->payload);
        } catch (Throwable $exception) {
            report($exception);
            $payload = HomeBlockRegistry::defaults($type);
        }

        return [
            'type' => $type,
            'partial' => $partial,
            'anchor' => $anchor,
            'nav_label' => HomeBlockRegistry::navLabel($type),
            'payload' => $payload,
        ];
    }

    /**
     * @return Collection<int, array{
     *     type: string,
     *     partial: string,
     *     anchor: ?string,
     *     nav_label: ?string,
     *     payload: array<string, mixed>
     * }>
     */
    private function fallbackSections(): Collection
    {
        return collect(HomeBlockRegistry::systemBlueprints())
            ->map(function (array $blueprint): ?array {
                $type = $blueprint['type'];
                $partial = HomeBlockRegistry::partial($type);

                if (! is_string($partial) || $partial === '') {
                    return null;
                }

                return [
                    'type' => $type,
                    'partial' => $partial,
                    'anchor' => HomeBlockRegistry::anchor($type),
                    'nav_label' => HomeBlockRegistry::navLabel($type),
                    'payload' => HomeBlockRegistry::mergePayload($type, $blueprint['payload']),
                ];
            })
            ->filter()
            ->values();
    }
}
