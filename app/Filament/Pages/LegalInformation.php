<?php

namespace App\Filament\Pages;

use App\Models\LegalDocument;
use App\Support\CookieConsent;
use App\Support\LegalDocuments;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\QueryException;
use Illuminate\Support\HtmlString;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class LegalInformation extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static string|UnitEnum|null $navigationGroup = 'Правовая информация';

    protected static ?string $navigationLabel = 'Документы и cookie';

    protected static ?string $title = 'Правовая информация';

    protected static ?string $slug = 'legal';

    protected static ?int $navigationSort = 1;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        LegalDocument::ensureDefaults();
        $this->form->fill($this->formState());
    }

    public function getSubheading(): ?string
    {
        return 'Тексты, реквизиты оператора и аналитика. Счётчик не загружается, пока посетитель сам не разрешит аналитические cookie.';
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Html::make(new HtmlString($this->versionHistory())),
                Form::make([EmbeddedSchema::make('form')])
                    ->id('legal-information-form')
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
                Section::make('Оператор персональных данных')
                    ->description('Эти поля публикуются на страницах политики и согласия. Не заполняйте их вымышленными сведениями.')
                    ->schema([
                        TextInput::make('operator_name')->label('Наименование или ФИО')->maxLength(255),
                        Textarea::make('operator_address')->label('Адрес')->rows(2)->maxLength(500),
                        TextInput::make('operator_email')->label('Email')->email()->maxLength(180),
                        TextInput::make('operator_phone')->label('Телефон')->maxLength(40),
                        Textarea::make('operator_extra')->label('Иные сведения')->rows(3)->maxLength(1000),
                        TextInput::make('retention_days')
                            ->label('Срок хранения заявок, дней')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(3650)
                            ->helperText('Пустое поле отключает автоматическое удаление. Сайт не подаёт уведомление в Роскомнадзор и не проверяет, что база данных находится в Российской Федерации.'),
                    ])
                    ->columns(2),
                Section::make('Политика обработки персональных данных')
                    ->description('Публичный адрес: /privacy-policy. Редактирование здесь, а не в разделе «Страницы»: для согласия нужно версионирование.')
                    ->schema([
                        TextInput::make('privacy_title')->label('Заголовок')->required()->maxLength(255),
                        Toggle::make('privacy_published')->label('Опубликована'),
                        RichEditor::make('privacy_body')->label('Текст')->required()->columnSpanFull(),
                        TextInput::make('privacy_meta_title')->label('SEO Title')->maxLength(255),
                        Textarea::make('privacy_meta_description')->label('SEO Description')->rows(3)->maxLength(500),
                    ]),
                Section::make('Согласие на обработку персональных данных')
                    ->description('Отдельный документ: /personal-data-consent. Изменение заголовка или текста создаёт новую версию и не переписывает уже полученные согласия. SEO-поля версию не повышают.')
                    ->schema([
                        TextInput::make('consent_title')->label('Заголовок')->required()->maxLength(255),
                        Toggle::make('consent_published')->label('Опубликовано'),
                        RichEditor::make('consent_body')->label('Текст')->required()->columnSpanFull(),
                        TextInput::make('consent_meta_title')->label('SEO Title')->maxLength(255),
                        Textarea::make('consent_meta_description')->label('SEO Description')->rows(3)->maxLength(500),
                    ]),
                Section::make('Cookie')
                    ->schema([
                        Textarea::make('banner')->label('Текст баннера')->required()->rows(4)->columnSpanFull(),
                        Textarea::make('settings')->label('Текст окна настроек')->required()->rows(4)->columnSpanFull(),
                        TextInput::make('notice_version')
                            ->label('Версия cookie-уведомления')
                            ->required()
                            ->maxLength(40)
                            ->helperText('Если изменить версию, посетители снова увидят баннер. Прежний выбор не считается согласием с новым текстом.'),
                        TextInput::make('necessary_title')->label('Название необходимых')->required()->maxLength(80),
                        Textarea::make('necessary_text')->label('Описание необходимых')->required()->rows(2),
                        TextInput::make('analytics_title')->label('Название аналитических')->required()->maxLength(80),
                        Textarea::make('analytics_text')->label('Описание аналитических')->required()->rows(2),
                        TextInput::make('marketing_title')->label('Название маркетинговых')->required()->maxLength(80),
                        Textarea::make('marketing_text')->label('Описание маркетинговых')->required()->rows(2),
                    ])
                    ->columns(2),
                Section::make('Аналитика')
                    ->description('Даже при включённой интеграции скрипт ждёт согласия на аналитические cookie.')
                    ->schema([
                        Toggle::make('analytics_enabled')->label('Разрешить загрузку аналитики после согласия'),
                        TextInput::make('analytics_service')
                            ->label('Сервис')
                            ->default('yandex')
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Сейчас поддерживается Яндекс Метрика. Другой сервис потребует отдельного подключения в коде.'),
                        TextInput::make('analytics_counter_id')
                            ->label('Номер счётчика Яндекс Метрики')
                            ->maxLength(15)
                            ->rules(['nullable', 'regex:/^\d{4,15}$/'])
                            ->helperText('Пустое поле или выключенный тумблер полностью отключают счётчик. Даже при заполненном номере tag.js не загружается, пока посетитель не разрешит аналитические cookie.'),
                    ])
                    ->columns(2),
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
            LegalDocument::ensureDefaults();
            $this->document(LegalDocuments::PRIVACY)->saveRevision([
                'title' => (string) $state['privacy_title'],
                'slug' => 'privacy-policy',
                'body' => (string) $state['privacy_body'],
                'is_published' => (bool) ($state['privacy_published'] ?? false),
                'meta_title' => $state['privacy_meta_title'] ?? null,
                'meta_description' => $state['privacy_meta_description'] ?? null,
            ]);
            $this->document(LegalDocuments::CONSENT)->saveRevision([
                'title' => (string) $state['consent_title'],
                'slug' => 'personal-data-consent',
                'body' => (string) $state['consent_body'],
                'is_published' => (bool) ($state['consent_published'] ?? false),
                'meta_title' => $state['consent_meta_title'] ?? null,
                'meta_description' => $state['consent_meta_description'] ?? null,
            ]);
            app(CookieConsent::class)->save($state);
        } catch (QueryException) {
            Notification::make()
                ->danger()
                ->title('Не удалось сохранить')
                ->body('Проверьте, что миграции базы данных выполнены.')
                ->send();

            return;
        }

        $this->form->fill($this->formState());

        Notification::make()
            ->success()
            ->title('Правовая информация сохранена')
            ->send();
    }

    /**
     * @return array<string, mixed>
     */
    private function formState(): array
    {
        LegalDocument::ensureDefaults();
        $privacy = $this->document(LegalDocuments::PRIVACY);
        $consent = $this->document(LegalDocuments::CONSENT);
        $settings = app(CookieConsent::class)->formState();

        return array_merge($settings, [
            'privacy_title' => $privacy->title,
            'privacy_body' => $privacy->body,
            'privacy_published' => $privacy->is_published,
            'privacy_meta_title' => $privacy->meta_title,
            'privacy_meta_description' => $privacy->meta_description,
            'consent_title' => $consent->title,
            'consent_body' => $consent->body,
            'consent_published' => $consent->is_published,
            'consent_meta_title' => $consent->meta_title,
            'consent_meta_description' => $consent->meta_description,
            'analytics_service' => 'yandex',
        ]);
    }

    private function document(string $key): LegalDocument
    {
        $document = LegalDocument::query()->where('key', $key)->first();

        if (! $document instanceof LegalDocument) {
            LegalDocument::ensureDefaults();
            $document = LegalDocument::query()->where('key', $key)->firstOrFail();
        }

        return $document;
    }

    private function versionHistory(): string
    {
        LegalDocument::ensureDefaults();
        $blocks = ['<div style="margin-bottom:1.5rem"><p><strong>История версий согласия.</strong> Старые редакции только для просмотра. Новая запись появляется, когда меняется заголовок или текст.</p>'];

        $consent = LegalDocument::query()->where('key', LegalDocuments::CONSENT)->first();

        if ($consent instanceof LegalDocument) {
            foreach ($consent->versions()->orderByDesc('version')->get() as $version) {
                $excerpt = e(mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags($version->body)) ?? ''), 0, 280));
                $blocks[] = '<p>Версия '.e((string) $version->version).' · '.e((string) optional($version->created_at)->format('d.m.Y H:i')).'<br>'.$excerpt.'</p>';
            }
        }

        $blocks[] = '</div>';

        return implode('', $blocks);
    }
}
