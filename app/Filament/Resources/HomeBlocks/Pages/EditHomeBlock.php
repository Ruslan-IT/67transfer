<?php

namespace App\Filament\Resources\HomeBlocks\Pages;

use App\Filament\Resources\HomeBlocks\HomeBlockResource;
use App\Models\HomeBlock;
use App\Support\HomeBlockRegistry;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHomeBlock extends EditRecord
{
    protected static string $resource = HomeBlockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn (): bool => $this->record instanceof HomeBlock && $this->record->isSystem()),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['is_system']);

        if ($this->record instanceof HomeBlock) {
            $data['type'] = $this->record->type;
        }

        $type = is_string($data['type'] ?? null) ? $data['type'] : '';

        if (HomeBlockRegistry::isKnown($type)) {
            $data['payload'] = HomeBlockRegistry::mergePayload(
                $type,
                is_array($data['payload'] ?? null) ? $data['payload'] : [],
            );
        }

        return $data;
    }
}
