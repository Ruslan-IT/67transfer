<?php

namespace App\Filament\Resources\BlogPosts;

use App\Filament\Resources\BlogPosts\Pages\CreateBlogPost;
use App\Filament\Resources\BlogPosts\Pages\EditBlogPost;
use App\Filament\Resources\BlogPosts\Pages\ListBlogPosts;
use App\Models\BlogPost;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class BlogPostResource extends Resource
{
    protected static ?string $model = BlogPost::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $slug = 'blog-posts';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string|UnitEnum|null $navigationGroup = 'Контент';

    protected static ?string $navigationLabel = 'Блог';

    protected static ?string $modelLabel = 'статья';

    protected static ?string $pluralModelLabel = 'Статьи';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Статья')
                    ->schema([
                        TextInput::make('title')
                            ->label('Заголовок')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(180)
                            ->unique(ignoreRecord: true)
                            ->helperText('URL: /blog/slug'),
                        TextInput::make('heading')
                            ->label('H1')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Основной заголовок на странице статьи.'),
                        TextInput::make('category')
                            ->label('Категория')
                            ->maxLength(120),
                        Textarea::make('excerpt')
                            ->label('Анонс')
                            ->required()
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpanFull(),
                        RichEditor::make('content')
                            ->label('Полный текст')
                            ->required()
                            ->columnSpanFull(),
                        DateTimePicker::make('published_at')
                            ->label('Дата публикации')
                            ->seconds(false)
                            ->default(now()),
                        Select::make('is_published')
                            ->label('Статус')
                            ->options([
                                0 => 'Черновик',
                                1 => 'Опубликовано',
                            ])
                            ->default(0)
                            ->required(),
                    ])
                    ->columns(2)
            ->columnSpanFull(),
                Section::make('Главная фотография')
                    ->schema([
                        FileUpload::make('image')
                            ->label('Изображение')
                            ->image()
                            ->imagePreviewHeight('220')
                            ->disk('public')
                            ->directory('blog')
                            ->visibility('public')
                            ->required()
                            ->maxSize(4096),
                        TextInput::make('image_alt')
                            ->label('Alt изображения')
                            ->maxLength(255),
                    ]),
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
                    ])
                ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('Фото')
                    ->disk('public'),
                TextColumn::make('title')
                    ->label('Заголовок')
                    ->searchable()
                    ->sortable()
                    ->limit(40),
                TextColumn::make('category')
                    ->label('Категория')
                    ->placeholder('—'),
                TextColumn::make('published_at')
                    ->label('Дата')
                    ->date('d.m.Y')
                    ->sortable(),
                IconColumn::make('is_published')
                    ->label('Опубликовано')
                    ->boolean(),
            ])
            ->defaultSort('published_at', 'desc')
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
            'index' => ListBlogPosts::route('/'),
            'create' => CreateBlogPost::route('/create'),
            'edit' => EditBlogPost::route('/{record}/edit'),
        ];
    }
}
