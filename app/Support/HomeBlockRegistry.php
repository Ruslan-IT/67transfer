<?php

namespace App\Support;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class HomeBlockRegistry
{
    public const HOW_IT_WORKS = 'how_it_works';

    public const ABOUT = 'about';

    public const FAQ = 'faq';

    public const CTA = 'cta';

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::definitions());
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::definitions() as $key => $definition) {
            $options[$key] = $definition['label'];
        }

        return $options;
    }

    public static function label(string $type): ?string
    {
        return self::definitions()[$type]['label'] ?? null;
    }

    public static function partial(string $type): ?string
    {
        return self::definitions()[$type]['partial'] ?? null;
    }

    public static function anchor(string $type): ?string
    {
        return self::definitions()[$type]['anchor'] ?? null;
    }

    public static function navLabel(string $type): ?string
    {
        return self::definitions()[$type]['nav_label'] ?? null;
    }

    public static function isKnown(string $type): bool
    {
        return array_key_exists($type, self::definitions());
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(string $type): array
    {
        return self::definitions()[$type]['defaults'] ?? [];
    }

    /**
     * @param  array<string, mixed>|null  $payload
     * @return array<string, mixed>
     */
    public static function mergePayload(string $type, ?array $payload): array
    {
        if (! self::isKnown($type)) {
            return [];
        }

        $defaults = self::defaults($type);
        $incoming = is_array($payload) ? $payload : [];

        return match ($type) {
            self::HOW_IT_WORKS => self::mergeHow($defaults, $incoming),
            self::ABOUT => self::mergeAbout($defaults, $incoming),
            self::FAQ => self::mergeFaq($defaults, $incoming),
            self::CTA => self::mergeCta($defaults, $incoming),
            default => $defaults,
        };
    }

    /**
     * @return list<\Filament\Forms\Components\Component>
     */
    public static function formComponents(string $type): array
    {
        return match ($type) {
            self::HOW_IT_WORKS => [
                TextInput::make('payload.kicker')
                    ->label('Надзаголовок')
                    ->maxLength(120),
                TextInput::make('payload.heading')
                    ->label('Заголовок')
                    ->maxLength(180),
                Textarea::make('payload.note')
                    ->label('Примечание под шагами')
                    ->rows(2)
                    ->maxLength(400),
                Repeater::make('payload.steps')
                    ->label('Шаги')
                    ->schema([
                        TextInput::make('title')
                            ->label('Название')
                            ->required()
                            ->maxLength(120),
                        Textarea::make('description')
                            ->label('Описание')
                            ->required()
                            ->rows(2)
                            ->maxLength(400),
                    ])
                    ->reorderable()
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                    ->addActionLabel('Добавить шаг')
                    ->columnSpanFull(),
            ],
            self::ABOUT => [
                TextInput::make('payload.kicker')
                    ->label('Надзаголовок')
                    ->maxLength(120),
                Textarea::make('payload.heading')
                    ->label('Заголовок')
                    ->rows(2)
                    ->maxLength(180),
                Textarea::make('payload.body')
                    ->label('Основной текст')
                    ->rows(5)
                    ->maxLength(2000),
                Repeater::make('payload.facts')
                    ->label('Пункты')
                    ->schema([
                        TextInput::make('text')
                            ->label('Текст')
                            ->required()
                            ->maxLength(120),
                    ])
                    ->reorderable()
                    ->addActionLabel('Добавить пункт')
                    ->columnSpanFull(),
            ],
            self::FAQ => [
                TextInput::make('payload.kicker')
                    ->label('Надзаголовок')
                    ->maxLength(120),
                TextInput::make('payload.heading')
                    ->label('Заголовок')
                    ->maxLength(180),
                Repeater::make('payload.items')
                    ->label('Вопросы и ответы')
                    ->schema([
                        TextInput::make('question')
                            ->label('Вопрос')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('answer')
                            ->label('Ответ')
                            ->required()
                            ->rows(3)
                            ->maxLength(2000),
                    ])
                    ->reorderable()
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['question'] ?? null)
                    ->addActionLabel('Добавить вопрос')
                    ->columnSpanFull(),
            ],
            self::CTA => [
                TextInput::make('payload.kicker')
                    ->label('Надзаголовок')
                    ->maxLength(120),
                TextInput::make('payload.heading')
                    ->label('Заголовок')
                    ->maxLength(180),
                Textarea::make('payload.text')
                    ->label('Описание')
                    ->rows(3)
                    ->maxLength(500),
                TextInput::make('payload.button_label')
                    ->label('Текст кнопки')
                    ->maxLength(80),
            ],
            default => [],
        };
    }

    /**
     * Seed / fallback order for the four starter blocks.
     *
     * @return list<array{type: string, name: string, sort_order: int, payload: array<string, mixed>}>
     */
    public static function systemBlueprints(): array
    {
        $order = 0;
        $blocks = [];

        foreach ([self::HOW_IT_WORKS, self::ABOUT, self::FAQ, self::CTA] as $type) {
            $blocks[] = [
                'type' => $type,
                'name' => self::label($type) ?? $type,
                'sort_order' => $order++,
                'payload' => self::defaults($type),
            ];
        }

        return $blocks;
    }

    /**
     * @return array<string, array{
     *     label: string,
     *     partial: string,
     *     anchor: ?string,
     *     nav_label: ?string,
     *     defaults: array<string, mixed>
     * }>
     */
    private static function definitions(): array
    {
        return [
            self::HOW_IT_WORKS => [
                'label' => 'Как это работает',
                'partial' => 'partials.home.how-it-works',
                'anchor' => 'how',
                'nav_label' => 'Как это работает',
                'defaults' => [
                    'kicker' => 'Как это работает',
                    'heading' => 'От заявки до поездки',
                    'note' => 'Отправленная заявка ещё не подтверждает поездку.',
                    'steps' => [
                        [
                            'title' => 'Оставьте заявку',
                            'description' => 'Укажите маршрут и удобный контакт. Отправка бесплатна.',
                        ],
                        [
                            'title' => 'Согласуйте поездку',
                            'description' => 'Водитель или представитель обсудит с вами автомобиль, стоимость и условия.',
                        ],
                        [
                            'title' => 'Оплатите после поездки',
                            'description' => 'Напрямую водителю. Способ оплаты согласуйте заранее.',
                        ],
                    ],
                ],
            ],
            self::ABOUT => [
                'label' => 'О сервисе',
                'partial' => 'partials.home.about',
                'anchor' => 'about',
                'nav_label' => 'О сервисе',
                'defaults' => [
                    'kicker' => 'О сервисе',
                    'heading' => "«ТРАНСФЕР\nи точка!»",
                    'body' => 'Информационный сервис для подачи заявок на поездки по Европе. Мы принимаем заявку и передаём её независимому водителю или его представителю. Дальнейшие условия поездки вы согласуете напрямую.',
                    'facts' => [
                        ['text' => 'Бесплатная заявка'],
                        ['text' => 'Прямое согласование'],
                    ],
                ],
            ],
            self::FAQ => [
                'label' => 'Вопросы и ответы',
                'partial' => 'partials.home.faq',
                'anchor' => 'questions',
                'nav_label' => 'Вопросы',
                'defaults' => [
                    'kicker' => 'Вопросы и ответы',
                    'heading' => 'Перед отправкой заявки',
                    'items' => [
                        [
                            'question' => 'Когда я узнаю стоимость?',
                            'answer' => 'Водитель или его представитель сообщит стоимость после уточнения маршрута и деталей поездки.',
                        ],
                        [
                            'question' => 'Заявка уже подтверждает поездку?',
                            'answer' => 'Нет. Поездку нужно отдельно согласовать с водителем или его представителем.',
                        ],
                        [
                            'question' => 'Кому и когда платить?',
                            'answer' => 'Напрямую водителю после поездки. Способ оплаты согласуйте заранее.',
                        ],
                        [
                            'question' => 'Как сообщить о детях или большом багаже?',
                            'answer' => 'Укажите сведения в соответствующих полях и пожеланиях формы. Необходимое кресло и подходящий автомобиль подтвердите при согласовании поездки.',
                        ],
                        [
                            'question' => 'Можно ли указать обратную поездку?',
                            'answer' => 'Да. Выберите «Туда и обратно» и заполните обратные дату и время. Условия обеих поездок согласуйте с исполнителем.',
                        ],
                        [
                            'question' => 'Как со мной свяжутся?',
                            'answer' => 'По оставленному контакту с учётом выбранного мессенджера. Укажите номер с кодом страны и проверьте возможность получить сообщение.',
                        ],
                    ],
                ],
            ],
            self::CTA => [
                'label' => 'Бесплатная заявка',
                'partial' => 'partials.home.cta',
                'anchor' => null,
                'nav_label' => null,
                'defaults' => [
                    'kicker' => 'Бесплатная заявка',
                    'heading' => 'Начнём с вашего маршрута',
                    'text' => 'Оставьте бесплатную заявку, чтобы обсудить поездку напрямую.',
                    'button_label' => 'Оставить заявку',
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    private static function mergeHow(array $defaults, array $incoming): array
    {
        $steps = [];
        $rawSteps = $incoming['steps'] ?? null;

        if (is_array($rawSteps) && $rawSteps !== []) {
            foreach ($rawSteps as $step) {
                if (! is_array($step)) {
                    continue;
                }

                $title = self::text($step['title'] ?? null);
                $description = self::text($step['description'] ?? null);

                if ($title === '' && $description === '') {
                    continue;
                }

                $steps[] = [
                    'title' => $title !== '' ? $title : 'Шаг',
                    'description' => $description,
                ];
            }
        }

        if ($steps === []) {
            $steps = $defaults['steps'];
        }

        return [
            'kicker' => self::text($incoming['kicker'] ?? null, $defaults['kicker']),
            'heading' => self::text($incoming['heading'] ?? null, $defaults['heading']),
            'note' => self::text($incoming['note'] ?? null, $defaults['note']),
            'steps' => $steps,
        ];
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    private static function mergeAbout(array $defaults, array $incoming): array
    {
        $facts = [];
        $rawFacts = $incoming['facts'] ?? null;

        if (is_array($rawFacts) && $rawFacts !== []) {
            foreach ($rawFacts as $fact) {
                $text = '';

                if (is_string($fact)) {
                    $text = self::text($fact);
                } elseif (is_array($fact)) {
                    $text = self::text($fact['text'] ?? reset($fact) ?: null);
                }

                if ($text !== '') {
                    $facts[] = ['text' => $text];
                }
            }
        }

        if ($facts === []) {
            $facts = $defaults['facts'];
        }

        return [
            'kicker' => self::text($incoming['kicker'] ?? null, $defaults['kicker']),
            'heading' => self::text($incoming['heading'] ?? null, $defaults['heading']),
            'body' => self::text($incoming['body'] ?? null, $defaults['body']),
            'facts' => $facts,
        ];
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    private static function mergeFaq(array $defaults, array $incoming): array
    {
        $items = [];
        $rawItems = $incoming['items'] ?? null;

        if (is_array($rawItems) && $rawItems !== []) {
            foreach ($rawItems as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $question = self::text($item['question'] ?? null);
                $answer = self::text($item['answer'] ?? null);

                if ($question === '' && $answer === '') {
                    continue;
                }

                $items[] = [
                    'question' => $question !== '' ? $question : 'Вопрос',
                    'answer' => $answer,
                ];
            }
        }

        if ($items === []) {
            $items = $defaults['items'];
        }

        return [
            'kicker' => self::text($incoming['kicker'] ?? null, $defaults['kicker']),
            'heading' => self::text($incoming['heading'] ?? null, $defaults['heading']),
            'items' => $items,
        ];
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    private static function mergeCta(array $defaults, array $incoming): array
    {
        return [
            'kicker' => self::text($incoming['kicker'] ?? null, $defaults['kicker']),
            'heading' => self::text($incoming['heading'] ?? null, $defaults['heading']),
            'text' => self::text($incoming['text'] ?? null, $defaults['text']),
            'button_label' => self::text($incoming['button_label'] ?? null, $defaults['button_label']),
        ];
    }

    private static function text(mixed $value, ?string $default = null): string
    {
        if (! is_string($value)) {
            return $default ?? '';
        }

        $value = trim($value);

        if ($value === '') {
            return $default ?? '';
        }

        return $value;
    }
}
