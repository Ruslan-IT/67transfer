<?php

namespace App\Filament\Pages;

use App\Support\FirstScreen;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
class FirstScreenSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Настройки';

    protected static ?string $navigationLabel = 'Первый экран';

    protected static ?string $title = 'Первый экран';

    protected static ?string $slug = 'first-screen';

    protected static ?int $navigationSort = 10;

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
        return 'Тексты, иконки и фон левой колонки на главной. Форма заявки здесь не настраивается.';
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('first-screen-form')
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
                Section::make('Тексты')
                    ->schema([
                        TextInput::make('eyebrow')
                            ->label('Надзаголовок')
                            ->required()
                            ->maxLength(120),
                        TextInput::make('title')
                            ->label('Заголовок H1')
                            ->required()
                            ->maxLength(180),
                        TextInput::make('lead')
                            ->label('Строка под заголовком')
                            ->required()
                            ->maxLength(180),
                        Textarea::make('note')
                            ->label('Пояснение')
                            ->required()
                            ->rows(3)
                            ->maxLength(400),
                    ])
                    ->columnSpanFull(),
                Section::make('Фон')
                    ->schema([
                        FileUpload::make('background')
                            ->label('Фоновое изображение')
                            ->image()
                            ->disk('public')
                            ->directory('first-screen')
                            ->visibility('public')
                            ->maxSize(4096)
                            ->helperText('Рисунок располагается слева, за текстом, без растягивания. Пустое поле оставляет экран без изображения.'),
                    ])
                    ->columnSpanFull(),
                $this->blockSection('Бесплатная заявка', 0),
                $this->blockSection('Подтверждение менеджером', 1),
                $this->blockSection('Удобно для всей семьи', 2),
            ]);
    }

    private function blockSection(string $heading, int $index): Section
    {
        return Section::make($heading)
            ->schema([
                TextInput::make('blocks.'.$index.'.title')
                    ->label('Заголовок')
                    ->required()
                    ->maxLength(120),
                Textarea::make('blocks.'.$index.'.text')
                    ->label('Пояснение')
                    ->required()
                    ->rows(2)
                    ->maxLength(300),
                FileUpload::make('blocks.'.$index.'.icon')
                    ->label('Иконка SVG')
                    ->acceptedFileTypes(['image/svg+xml', 'text/xml', 'application/xml', 'text/plain'])
                    ->disk('public')
                    ->directory('first-screen/icons')
                    ->visibility('public')
                    ->maxSize(128)
                    ->helperText('Только SVG. Если файл не загружен, остаётся стандартная иконка.'),
            ])
            ->columnSpanFull();
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
            app(FirstScreen::class)->update($state);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (QueryException) {
            Notification::make()
                ->danger()
                ->title('Не удалось сохранить первый экран')
                ->body('Проверьте, что миграции базы данных выполнены.')
                ->send();

            return;
        }

        $this->form->fill($this->formState());

        Notification::make()
            ->success()
            ->title('Первый экран сохранён')
            ->send();
    }

    /**
     * @return array<string, mixed>
     */
    private function formState(): array
    {
        $data = app(FirstScreen::class)->data();

        return [
            'eyebrow' => $data['eyebrow'],
            'title' => $data['title'],
            'lead' => $data['lead'],
            'note' => $data['note'],
            'background' => $data['background'],
            'blocks' => array_map(fn (array $block): array => [
                'title' => $block['title'],
                'text' => $block['text'],
                'icon' => $block['icon'],
            ], $data['blocks']),
        ];
    }
}
