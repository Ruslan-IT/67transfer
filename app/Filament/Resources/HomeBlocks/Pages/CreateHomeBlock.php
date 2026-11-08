<?php

namespace App\Filament\Resources\HomeBlocks\Pages;

use App\Filament\Resources\HomeBlocks\HomeBlockResource;
use App\Models\HomeBlock;
use App\Support\HomeBlockRegistry;
use Filament\Resources\Pages\CreateRecord;

class CreateHomeBlock extends CreateRecord
{
    protected static string $resource = HomeBlockResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['is_system'] = false;

        $type = is_string($data['type'] ?? null) ? $data['type'] : '';

        if (HomeBlockRegistry::isKnown($type)) {
            $data['payload'] = HomeBlockRegistry::mergePayload(
                $type,
                is_array($data['payload'] ?? null) ? $data['payload'] : [],
            );
        }

        if (! isset($data['sort_order']) || $data['sort_order'] === '' || $data['sort_order'] === null) {
            $data['sort_order'] = ((int) HomeBlock::query()->max('sort_order')) + 1;
        }

        return $data;
    }
}
