<?php

namespace App\Filament\Resources\SitePages;

use App\Filament\Resources\SitePages\Pages\CreatePage;
use App\Filament\Resources\SitePages\Pages\EditPage;
use App\Filament\Resources\SitePages\Pages\ListPages;
use App\Models\Page;
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
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Контент';

    protected static ?string $navigationLabel = 'Страницы';

    protected static ?string $slug = 'pages';

    protected static ?string $modelLabel = 'страница';

    protected static ?string $pluralModelLabel = 'Страницы';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Страница')
                    ->schema([
                        TextInput::make('title')
                            ->label('Название')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(180)
                            ->unique(ignoreRecord: true)
                            ->readOnly(fn (?Model $record): bool => $record instanceof Page && $record->isSystem()),
                        Toggle::make('is_published')
                            ->label('Опубликована')
                            ->default(true),
                    ])
                    ->columns(2)
                ->columnSpanFull(),
                Section::make('Главная')
                    ->visible(fn (Get $get): bool => $get('slug') === 'home')
                    ->schema([
                        Textarea::make('content.hero_title')
                            ->label('Заголовок hero')
                            ->rows(2),
                        TextInput::make('content.hero_subtitle')
                            ->label('Подзаголовок'),
                        Textarea::make('content.hero_description')
                            ->label('Описание hero')
                            ->rows(4),
                        TextInput::make('content.hero_button')
                            ->label('Текст кнопки'),
                        TextInput::make('content.hero_image_alt')
                            ->label('Alt главного фото'),
                        TextInput::make('content.hero_image_label')
                            ->label('Подпись на фото'),
                        TextInput::make('content.intro_kicker')
                            ->label('Метка блока 01'),
                        Textarea::make('content.intro_heading')
                            ->label('Заголовок блока 01')
                            ->rows(2),
                        Textarea::make('content.intro_text')
                            ->label('Текст блока 01')
                            ->rows(4),
                        TextInput::make('content.destinations_kicker')
                            ->label('Метка блока направлений'),
                        TextInput::make('content.destinations_heading')
                            ->label('Заголовок блока направлений'),
                        Textarea::make('content.destinations_text')
                            ->label('Текст блока направлений')
                            ->rows(3),
                        TextInput::make('content.how_kicker')
                            ->label('Метка блока «Как это работает»'),
                        TextInput::make('content.how_heading')
                            ->label('Заголовок «Как это работает»'),
                        Repeater::make('content.how_items')
                            ->label('Пункты')
                            ->simple(TextInput::make('text')->required()),
                        TextInput::make('content.footer_descriptor')
                            ->label('Текст в подвале'),
                        Textarea::make('content.footer_note')
                            ->label('Нижняя сноска')
                            ->rows(2),
                    ]),
                Section::make('Направления (список)')
                    ->visible(fn (Get $get): bool => $get('slug') === 'destinations')
                    ->schema([
                        TextInput::make('content.eyebrow')
                            ->label('Надзаголовок'),
                        TextInput::make('content.heading')
                            ->label('Заголовок'),
                        Textarea::make('content.lede')
                            ->label('Вводный текст')
                            ->rows(3),
                    ]),
                Section::make('Информация')
                    ->visible(fn (Get $get): bool => $get('slug') === 'information')
                    ->schema([
                        TextInput::make('content.eyebrow')
                            ->label('Надзаголовок'),
                        TextInput::make('content.heading')
                            ->label('Заголовок'),
                        Textarea::make('content.lede')
                            ->label('Вводный текст')
                            ->rows(3),
                        TextInput::make('content.say_heading')
                            ->label('Заголовок «Как мы говорим»'),
                        Repeater::make('content.say_items')
                            ->label('Как мы говорим')
                            ->simple(TextInput::make('text')->required()),
                        TextInput::make('content.avoid_heading')
                            ->label('Заголовок «Как мы не говорим»'),
                        Repeater::make('content.avoid_items')
                            ->label('Как мы не говорим')
                            ->simple(TextInput::make('text')->required()),
                        TextInput::make('content.legal_eyebrow')
                            ->label('Надзаголовок правовой оговорки'),
                        TextInput::make('content.legal_heading')
                            ->label('Заголовок правовой оговорки'),
                        RichEditor::make('content.legal_p1')
                            ->label('Правовой текст 1'),
                        RichEditor::make('content.legal_p2')
                            ->label('Правовой текст 2'),
                        Textarea::make('content.legal_fineprint')
                            ->label('Мелкий шрифт')
                            ->rows(3)

                    ])
                ->columnSpanFull(),
                Section::make('Контакты')
                    ->visible(fn (Get $get): bool => $get('slug') === 'contacts')
                    ->schema([
                        TextInput::make('content.eyebrow')
                            ->label('Надзаголовок'),
                        TextInput::make('content.heading')
                            ->label('H1')
                            ->helperText('Основной заголовок страницы. Также используется как SEO H1.'),
                        Textarea::make('content.lede')
                            ->label('Короткое описание')
                            ->rows(3),
                        TextInput::make('content.email')
                            ->label('Email')
                            ->email(),
                        TextInput::make('content.phone')
                            ->label('Телефон'),
                        TextInput::make('content.whatsapp')
                            ->label('WhatsApp')
                            ->helperText('Номер или ссылка https://wa.me/...'),
                        TextInput::make('content.telegram')
                            ->label('Telegram')
                            ->helperText('Username, @username или ссылка https://t.me/...'),
                        Textarea::make('content.address')
                            ->label('Адрес')
                            ->rows(2),
                        Textarea::make('content.extra_text')
                            ->label('Дополнительный текст')
                            ->rows(3),
                        TextInput::make('content.button_text')
                            ->label('Текст кнопки обратной связи'),
                        Textarea::make('content.map_embed')
                            ->label('Код карты (iframe)')
                            ->rows(4)
                            ->helperText('Вставьте iframe Google Maps, Яндекс.Карт или OpenStreetMap. На сайте будет использован только безопасный src.'),
                    ]),
                Section::make('Основной текст')
                    ->visible(fn (Get $get): bool => ! in_array($get('slug'), Page::SYSTEM_SLUGS, true))
                    ->schema([
                        RichEditor::make('content.body')
                            ->label('Контент')
                            ->columnSpanFull(),
                    ]),
                Section::make('Изображения')
                    ->schema([
                        FileUpload::make('hero_image')
                            ->label('Главное изображение')
                            ->image()
                            ->disk('public')
                            ->directory('pages/hero')
                            ->visibility('public')
                            ->maxSize(4096),
                        FileUpload::make('image')
                            ->label('Дополнительное изображение')
                            ->image()
                            ->disk('public')
                            ->directory('pages')
                            ->visibility('public')
                            ->maxSize(4096),
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
                            ->label('Keywords')
                            ->maxLength(255),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),
                IconColumn::make('is_published')
                    ->label('Опубликована')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('Обновлена')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('title')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (Page $record): bool => $record->isSystem()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof Page && ! $record->isSystem();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
