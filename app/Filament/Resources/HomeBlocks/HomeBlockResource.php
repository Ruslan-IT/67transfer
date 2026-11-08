<?php

namespace App\Filament\Resources\HomeBlocks;

use App\Filament\Resources\HomeBlocks\Pages\CreateHomeBlock;
use App\Filament\Resources\HomeBlocks\Pages\EditHomeBlock;
use App\Filament\Resources\HomeBlocks\Pages\ListHomeBlocks;
use App\Models\HomeBlock;
use App\Support\HomeBlockRegistry;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class HomeBlockResource extends Resource
{
    protected static ?string $model = HomeBlock::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Контент';

    protected static ?string $navigationLabel = 'Блоки главной страницы';

    protected static ?string $modelLabel = 'блок главной';

    protected static ?string $pluralModelLabel = 'Блоки главной страницы';

    protected static ?string $slug = 'home-blocks';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Блок')
                    ->schema([
                        Select::make('type')
                            ->label('Тип')
                            ->options(HomeBlockRegistry::options())
                            ->required()
                            ->live()
                            ->native(false)
                            ->disabled(fn (?HomeBlock $record): bool => $record !== null)
                            ->dehydrated()
                            ->afterStateUpdated(function (?string $state, Set $set, ?HomeBlock $record): void {
                                if ($record !== null || ! is_string($state) || $state === '') {
                                    return;
                                }

                                $label = HomeBlockRegistry::label($state);

                                if ($label) {
                                    $set('name', $label);
                                }

                                $set('payload', HomeBlockRegistry::defaults($state));
                            })
                            ->helperText('Тип задаётся при создании. Новые типы добавляются в реестре кода.'),
                        TextInput::make('name')
                            ->label('Название в админке')
                            ->required()
                            ->maxLength(180),
                        Toggle::make('is_enabled')
                            ->label('Показывать на главной')
                            ->default(true),
                        TextInput::make('sort_order')
                            ->label('Порядок')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->helperText('Можно также менять перетаскиванием в списке.'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Содержимое')
                    ->schema(function (Get $get): array {
                        $type = (string) ($get('type') ?? '');

                        return HomeBlockRegistry::isKnown($type)
                            ? HomeBlockRegistry::formComponents($type)
                            : [];
                    })
                    ->visible(fn (Get $get): bool => filled($get('type')) && HomeBlockRegistry::isKnown((string) $get('type')))
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->label('№')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Тип')
                    ->formatStateUsing(fn (string $state): string => HomeBlockRegistry::label($state) ?? $state)
                    ->badge(),
                IconColumn::make('is_enabled')
                    ->label('На сайте')
                    ->boolean(),
                IconColumn::make('is_system')
                    ->label('Системный')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Обновлён')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (HomeBlock $record): bool => $record->isSystem()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof HomeBlock && ! $record->isSystem();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHomeBlocks::route('/'),
            'create' => CreateHomeBlock::route('/create'),
            'edit' => EditHomeBlock::route('/{record}/edit'),
        ];
    }
}
