<?php

namespace App\Filament\Pages;

use App\Support\GooglePlaces;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\QueryException;
use Illuminate\Support\HtmlString;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class GooglePlacesSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'Настройки';

    protected static ?string $navigationLabel = 'Google Places';

    protected static ?string $title = 'Google Places';

    protected static ?string $slug = 'google-places';

    protected static ?int $navigationSort = 20;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->formState());
    }

    public function getSubheading(): ?string
    {
        return 'Ключи Google Maps Platform для автоподсказок мест. Заявки и расчёт маршрута здесь не включаются.';
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('google-places-form')
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
                Section::make('Интеграция')
                    ->schema([
                        Html::make(new HtmlString($this->instructions())),
                        Toggle::make('enabled')
                            ->label('Включить автоподсказки на сайте')
                            ->helperText('Пока выключено, форма на главной остаётся на месте, но запросы к Google не отправляются.'),
                        TextInput::make('language')
                            ->label('Язык подсказок')
                            ->required()
                            ->default('ru')
                            ->maxLength(12)
                            ->regex('/^[a-z]{2}(-[A-Za-z0-9]{2,8})?$/')
                            ->helperText('Код языка, например ru. Поиск не ограничивается типом «адрес» и не привязан к одной стране.')
                            ->validationMessages([
                                'regex' => 'Укажите код языка, например ru.',
                            ]),
                    ])
                    ->columnSpanFull(),
                Section::make('Браузерный ключ')
                    ->description('Используется только в браузере для Place Autocomplete (New).')
                    ->schema([
                        Text::make(fn (): string => app(GooglePlaces::class)->keyStatus('browser')),
                        TextInput::make('browser_api_key')
                            ->label('Новый браузерный ключ')
                            ->password()
                            ->revealable()
                            ->autocomplete('off')
                            ->rules(['nullable', 'regex:/^\S{10,512}$/'])
                            ->validationMessages([
                                'regex' => 'Ключ должен быть одной строкой без пробелов, от 10 символов.',
                            ])
                            ->helperText('Оставьте пустым, чтобы не менять сохранённый ключ. Не вставляйте сюда серверный ключ.'),
                        Toggle::make('clear_browser_key')
                            ->label('Удалить сохранённый браузерный ключ')
                            ->visible(fn (): bool => app(GooglePlaces::class)->hasBrowserKey())
                            ->helperText('Если одновременно ввести новый ключ, сохранится новый.'),
                    ])
                    ->columnSpanFull(),
                Section::make('Серверный ключ')
                    ->description('Хранится для следующих серверных запросов. Сейчас ни один серверный вызов Google не выполняется.')
                    ->schema([
                        Text::make(fn (): string => app(GooglePlaces::class)->keyStatus('server')),
                        TextInput::make('server_api_key')
                            ->label('Новый серверный ключ')
                            ->password()
                            ->revealable()
                            ->autocomplete('off')
                            ->rules(['nullable', 'regex:/^\S{10,512}$/'])
                            ->validationMessages([
                                'regex' => 'Ключ должен быть одной строкой без пробелов, от 10 символов.',
                            ])
                            ->helperText('Оставьте пустым, чтобы не менять сохранённый ключ. Значение не попадает в HTML и JavaScript.'),
                        Toggle::make('clear_server_key')
                            ->label('Удалить сохранённый серверный ключ')
                            ->visible(fn (): bool => app(GooglePlaces::class)->hasServerKey())
                            ->helperText('Если одновременно ввести новый ключ, сохранится новый.'),
                    ])
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
            app(GooglePlaces::class)->update($state);
        } catch (QueryException) {
            Notification::make()
                ->danger()
                ->title('Не удалось сохранить настройки')
                ->body('Проверьте, что миграции базы данных выполнены.')
                ->send();

            return;
        }

        $this->form->fill($this->formState());

        Notification::make()
            ->success()
            ->title('Настройки Google сохранены')
            ->send();
    }

    private function instructions(): string
    {
        return <<<'HTML'
<div class="text-sm leading-6 text-gray-600">
    <p>Страница только хранит ключи владельца сайта. Она не создаёт проект в Google Cloud и не включает биллинг.</p>
    <p class="mt-2">Для этой формы в Google Cloud нужны <strong>Maps JavaScript API</strong> и <strong>Places API (New)</strong>. Routes API пока не используется.</p>
    <p class="mt-2">Браузерный ключ ограничьте по HTTP referrers. Для локальной работы добавьте <code>http://67transfer.local/*</code> и <code>https://67transfer.local/*</code>, а перед публикацией — домен сайта. В ограничениях API оставьте Maps JavaScript API и Places API (New).</p>
    <p class="mt-2">Серверный ключ ограничьте по IP. Он сохраняется отдельно и на сайт не передаётся.</p>
</div>
HTML;
    }

    /**
     * @return array<string, mixed>
     */
    private function formState(): array
    {
        $google = app(GooglePlaces::class);

        return [
            'enabled' => $google->enabled(),
            'language' => $google->language(),
            'browser_api_key' => '',
            'server_api_key' => '',
            'clear_browser_key' => false,
            'clear_server_key' => false,
        ];
    }
}
