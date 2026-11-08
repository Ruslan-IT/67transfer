<?php

namespace App\Filament\Resources\Enquiries;

use App\Filament\Resources\Enquiries\Pages\EditEnquiry;
use App\Filament\Resources\Enquiries\Pages\ListEnquiries;
use App\Models\Enquiry;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class EnquiryResource extends Resource
{
    protected static ?string $model = Enquiry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static string|UnitEnum|null $navigationGroup = 'Заявки';

    protected static ?string $navigationLabel = 'Заявки';

    protected static ?string $modelLabel = 'заявка';

    protected static ?string $pluralModelLabel = 'Заявки';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Заявка')
                    ->schema([
                        TextInput::make('kind')
                            ->label('Тип')
                            ->formatStateUsing(fn (?Enquiry $record): string => $record?->kindLabel() ?? '')
                            ->disabled()
                            ->dehydrated(false),
                        Select::make('status')
                            ->label('Статус')
                            ->options(Enquiry::STATUSES)
                            ->required(),
                        TextInput::make('name')
                            ->label('Имя')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('phone')
                            ->label('Телефон')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('messenger')
                            ->label('Мессенджер')
                            ->formatStateUsing(fn (?Enquiry $record): string => $record?->messenger ?: 'Не указан')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('email')
                            ->label('Email')
                            ->disabled()
                            ->dehydrated(false),
                        Textarea::make('comment')
                            ->label('Комментарий')
                            ->disabled()
                            ->dehydrated(false)
                            ->rows(4)
                            ->columnSpanFull(),
                        Textarea::make('details')
                            ->label('Маршрут и параметры')
                            ->formatStateUsing(fn (?Enquiry $record): string => $record?->detailsText() ?? '')
                            ->disabled()
                            ->dehydrated(false)
                            ->rows(12)
                            ->columnSpanFull(),
                        TextInput::make('created_at')
                            ->label('Дата создания')
                            ->formatStateUsing(fn (?Enquiry $record): string => $record?->created_at?->timezone(config('app.timezone'))->format('d.m.Y H:i') ?? '')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('consented_at')
                            ->label('Согласие получено')
                            ->formatStateUsing(fn (?Enquiry $record): string => $record?->consented_at?->timezone(config('app.timezone'))->format('d.m.Y H:i') ?? 'Нет')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('consent_version')
                            ->label('Версия текста согласия')
                            ->formatStateUsing(fn (?Enquiry $record): string => $record?->consentVersion ? 'Версия '.$record->consentVersion->version : 'Не записана')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('kind')
                    ->label('Тип')
                    ->formatStateUsing(fn (string $state): string => Enquiry::KINDS[$state] ?? $state),
                TextColumn::make('name')
                    ->label('Имя')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable(),
                TextColumn::make('messenger')
                    ->label('Мессенджер')
                    ->placeholder('Не указан'),
                TextColumn::make('status')
                    ->label('Статус')
                    ->formatStateUsing(fn (string $state): string => Enquiry::STATUSES[$state] ?? $state),
            ])
            ->filters([
                SelectFilter::make('kind')
                    ->label('Тип')
                    ->options(Enquiry::KINDS),
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(Enquiry::STATUSES),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make()->label('Открыть'),
                DeleteAction::make()->requiresConfirmation(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()->requiresConfirmation(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEnquiries::route('/'),
            'edit' => EditEnquiry::route('/{record}/edit'),
        ];
    }
}
