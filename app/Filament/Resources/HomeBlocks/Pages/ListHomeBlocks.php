<?php

namespace App\Filament\Resources\HomeBlocks\Pages;

use App\Filament\Resources\HomeBlocks\HomeBlockResource;
use App\Support\HomeBlocks;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHomeBlocks extends ListRecords
{
    protected static string $resource = HomeBlockResource::class;

    public function mount(): void
    {
        parent::mount();

        app(HomeBlocks::class)->ensureSystemBlocks();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
