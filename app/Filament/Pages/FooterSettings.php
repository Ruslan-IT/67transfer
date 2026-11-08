<?php

namespace App\Filament\Pages;

use App\Support\SiteFooter;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class FooterSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3BottomLeft;

    protected static string|UnitEnum|null $navigationGroup = 'Настройки';

    protected static ?string $navigationLabel = 'Настройки футера';

    protected static ?string $title = 'Настройки футера';

    protected static ?string $slug = 'footer-settings';

    protected static ?int $navigationSort = 11;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->formState());
    }

    public function getSubheading(): ?string
    {
        return 'Контакты, мессенджеры и соцсети подвала главной. Юридические URL и кнопка cookie не меняются — только подписи.';
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('footer-settings-form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make($this->getFormActions()),
                    ]),
            ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Бренд')
                    ->schema([
                        FileUpload::make('logo')
                            ->label('Логотип')
                            ->image()
                            ->disk('public')
                            ->directory('footer')
                            ->visibility('public')
                            ->maxSize(2048)
                            ->helperText('Пустое поле оставляет стандартный белый логотип.'),
                        TextInput::make('logo_alt')
                            ->label('Alt логотипа')
                            ->required()
                            ->maxLength(120),
                        Textarea::make('brand_text')
                            ->label('Текст под логотипом')
                            ->required()
                            ->rows(3)
                            ->maxLength(400),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Контактная информация')
                    ->schema([
                        TextInput::make('contact_label')
                            ->label('Заголовок блока')
                            ->required()
                            ->maxLength(80),
                        Toggle::make('phone.enabled')
                            ->label('Показывать телефон')
                            ->inline(false),
                        TextInput::make('phone.display')
                            ->label('Телефон (отображение)')
                            ->maxLength(60),
                        TextInput::make('phone.tel')
                            ->label('Телефон для ссылки tel:')
                            ->maxLength(40)
                            ->helperText('Только цифры и +, например +34600000000.'),
                        Toggle::make('phone_extra.enabled')
                            ->label('Показывать доп. телефон')
                            ->inline(false),
                        TextInput::make('phone_extra.display')
                            ->label('Доп. телефон (отображение)')
                            ->maxLength(60),
                        TextInput::make('phone_extra.tel')
                            ->label('Доп. телефон для tel:')
                            ->maxLength(40),
                        Toggle::make('email.enabled')
                            ->label('Показывать email')
                            ->inline(false),
                        TextInput::make('email.display')
                            ->label('Email (отображение)')
                            ->maxLength(180),
                        TextInput::make('email.mailto')
                            ->label('Email для mailto:')
                            ->email()
                            ->maxLength(180),
                        Toggle::make('address.enabled')
                            ->label('Показывать адрес')
                            ->inline(false),
                        Textarea::make('address.text')
                            ->label('Адрес')
                            ->rows(2)
                            ->maxLength(300),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('WhatsApp')
                    ->schema([
                        Toggle::make('whatsapp.enabled')
                            ->label('Показывать WhatsApp')
                            ->inline(false),
                        TextInput::make('whatsapp.label')
                            ->label('Подпись')
                            ->required()
                            ->maxLength(40),
                        TextInput::make('whatsapp.phone')
                            ->label('Номер WhatsApp')
                            ->maxLength(40)
                            ->helperText('Будет собран URL https://wa.me/…, если поле ссылки пустое.'),
                        TextInput::make('whatsapp.url')
                            ->label('Ссылка на чат')
                            ->url()
                            ->maxLength(255)
                            ->helperText('Необязательно. Имеет приоритет над номером.'),
                        FileUpload::make('whatsapp.icon')
                            ->label('Иконка SVG')
                            ->acceptedFileTypes(['image/svg+xml', 'text/xml', 'application/xml', 'text/plain'])
                            ->disk('public')
                            ->directory('footer/icons')
                            ->visibility('public')
                            ->maxSize(128)
                            ->helperText('Пустое поле — стандартный логотип WhatsApp.'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Telegram')
                    ->schema([
                        Toggle::make('telegram.enabled')
                            ->label('Показывать Telegram')
                            ->inline(false),
                        TextInput::make('telegram.label')
                            ->label('Подпись')
                            ->required()
                            ->maxLength(40),
                        TextInput::make('telegram.username')
                            ->label('Username или канал')
                            ->maxLength(80)
                            ->helperText('Без @. Будет собран URL https://t.me/…, если поле ссылки пустое.'),
                        TextInput::make('telegram.url')
                            ->label('Ссылка на профиль или канал')
                            ->url()
                            ->maxLength(255)
                            ->helperText('Необязательно. Имеет приоритет над username.'),
                        FileUpload::make('telegram.icon')
                            ->label('Иконка SVG')
                            ->acceptedFileTypes(['image/svg+xml', 'text/xml', 'application/xml', 'text/plain'])
                            ->disk('public')
                            ->directory('footer/icons')
                            ->visibility('public')
                            ->maxSize(128)
                            ->helperText('Пустое поле — стандартный логотип Telegram.'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Социальные сети')
                    ->schema([
                        Repeater::make('socials')
                            ->label('Список')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Название')
                                    ->required()
                                    ->maxLength(80),
                                TextInput::make('url')
                                    ->label('URL')
                                    ->required()
                                    ->url()
                                    ->maxLength(255),
                                Toggle::make('enabled')
                                    ->label('Показывать')
                                    ->default(true),
                                FileUpload::make('icon')
                                    ->label('Иконка SVG')
                                    ->acceptedFileTypes(['image/svg+xml', 'text/xml', 'application/xml', 'text/plain'])
                                    ->disk('public')
                                    ->directory('footer/icons')
                                    ->visibility('public')
                                    ->maxSize(128),
                            ])
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                            ->addActionLabel('Добавить соцсеть')
                            ->default([])
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Section::make('Запасная ссылка')
                    ->description('Показывается, если нет ни одного контакта или мессенджера.')
                    ->schema([
                        Toggle::make('fallback.enabled')
                            ->label('Показывать ссылку «Задать вопрос»')
                            ->inline(false),
                        TextInput::make('fallback.label')
                            ->label('Текст ссылки')
                            ->required()
                            ->maxLength(80),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Юридические подписи')
                    ->description('Адреса страниц и действие кнопки cookie зафиксированы в коде.')
                    ->schema([
                        TextInput::make('legal.privacy_label')
                            ->label('Политика обработки данных')
                            ->required()
                            ->maxLength(120),
                        TextInput::make('legal.consent_label')
                            ->label('Согласие на обработку данных')
                            ->required()
                            ->maxLength(120),
                        TextInput::make('legal.cookie_label')
                            ->label('Настройки cookie')
                            ->required()
                            ->maxLength(80),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),
                Section::make('Нижняя строка')
                    ->schema([
                        TextInput::make('copyright')
                            ->label('Копирайт')
                            ->required()
                            ->maxLength(180)
                            ->helperText('Подставьте {year} для текущего года.'),
                        Toggle::make('show_owner')
                            ->label('Показывать строку оператора')
                            ->inline(false),
                        TextInput::make('owner_line')
                            ->label('Своя строка оператора')
                            ->maxLength(255)
                            ->helperText('Пустое поле подставляет имя и адрес из раздела «Правовая информация».'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Сохранить')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();

        try {
            app(SiteFooter::class)->update($state);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (QueryException) {
            Notification::make()
                ->danger()
                ->title('Не удалось сохранить футер')
                ->body('Проверьте, что миграции базы данных выполнены.')
                ->send();

            return;
        }

        $this->form->fill($this->formState());

        Notification::make()
            ->success()
            ->title('Настройки футера сохранены')
            ->send();
    }

    /**
     * @return array<string, mixed>
     */
    private function formState(): array
    {
        return app(SiteFooter::class)->data();
    }
}
