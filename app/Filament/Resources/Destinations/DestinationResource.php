<?php

namespace App\Filament\Resources\Destinations;

use App\Filament\Resources\Destinations\Pages\CreateDestination;
use App\Filament\Resources\Destinations\Pages\EditDestination;
use App\Filament\Resources\Destinations\Pages\ListDestinations;
use App\Models\Destination;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;
use UnitEnum;

class DestinationResource extends Resource
{
    protected static ?string $model = Destination::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'Контент';

    protected static ?string $navigationLabel = 'Города';

    protected static ?string $modelLabel = 'город';

    protected static ?string $pluralModelLabel = 'Города';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Город')
                    ->schema([
                        TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->maxLength(180),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(180)
                            ->unique(ignoreRecord: true)
                            ->rules([Rule::notIn(Destination::RESERVED_SLUGS)])
                            ->helperText('URL страницы: /slug. Только латиница, без пробелов.'),
                        TextInput::make('title')
                            ->label('Основной заголовок')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('territory')
                            ->label('Территория')
                            ->maxLength(180),
                        TextInput::make('tagline')
                            ->label('Подзаголовок')
                            ->maxLength(255),
                        RichEditor::make('content')
                            ->label('Текст')
                            ->columnSpanFull(),
                        Toggle::make('is_published')
                            ->label('Опубликован')
                            ->default(true),
                        TextInput::make('sort_order')
                            ->label('Порядок')
                            ->numeric()
                            ->default(0)
                            ->required(),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),
                Section::make('Изображения')
                    ->schema([

                        FileUpload::make('image')
                            ->label('Главное изображение')
                            ->image()
                            ->disk('public')
                            ->directory('destinations')
                            ->visibility('public')
                            ->maxSize(4096),

                        TextInput::make('image_alt')
                            ->label('Alt изображения')
                            ->maxLength(255),
                        FileUpload::make('badge')
                            ->label('Значок')
                            ->image()
                            ->disk('public')
                            ->directory('destinations/badges')
                            ->visibility('public')
                            ->maxSize(2048),
                    ])
                    ->columns(1),

                Section::make('Форма запроса')
                    ->schema([
                        TextInput::make('from_default')
                            ->label('Откуда (по умолчанию)')
                            ->maxLength(180),
                        TextInput::make('to_default')
                            ->label('Куда (по умолчанию)')
                            ->maxLength(180),
                        Repeater::make('routes')
                            ->label('Маршруты')
                            ->simple(TextInput::make('route')->required())
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('SEO')
                    ->schema([
                        TextInput::make('meta_title')
                            ->label('SEO Title')
                            ->maxLength(255),
                        Textarea::make('meta_description')
                            ->label('SEO Description')
                            ->rows(3)
                            ->maxLength(500),
                        TextInput::make('meta_keywords')
                            ->label('SEO Keywords')
                            ->maxLength(255),
                    ])
                    ->columnSpanFull()
                ->columns(1),
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
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),
                IconColumn::make('is_published')
                    ->label('Опубликован')
                    ->boolean(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDestinations::route('/'),
            'create' => CreateDestination::route('/create'),
            'edit' => EditDestination::route('/{record}/edit'),
        ];
    }
}
