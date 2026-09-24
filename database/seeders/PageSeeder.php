<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        Page::firstOrCreate(
            ['slug' => 'home'],
            [
                'title' => 'TRANSFER POINT: ЕВРОПА',
                'content' => [
                    'hero_title' => "Transfer Point:\nЕвропа",
                    'hero_subtitle' => 'Местная информация о трансферах в Европе',
                    'hero_description' => "Ваш ориентир по комфортным трансферам,\nмаршрутам и местному транспорту по всей Европе.\nПонятная информация. Простое планирование.",
                    'hero_button' => 'Смотреть трансферы',
                    'hero_image_alt' => 'Трансфер в Европе',
                    'hero_image_label' => 'ЕВРОПА',
                    'intro_kicker' => '01 / ТРАНСФЕР',
                    'intro_heading' => "Передвигаться по Европе\nдолжно быть просто.",
                    'intro_text' => "Практичная информация о местных трансферах,\nмаршрутах, аэропортах и направлениях.\nВсё, что нужно, чтобы уверенно спланировать поездку.",
                    'destinations_kicker' => '02 / Направления',
                    'destinations_heading' => 'Один бренд. Много локальных точек.',
                    'destinations_text' => 'TRANSFER POINT — это не один автомобиль и не один перевозчик. Это система доступа к местным предложениям трансфера. Формула всегда одна: TRANSFER POINT: [НАПРАВЛЕНИЕ].',
                    'how_kicker' => '03 / Как это работает',
                    'how_heading' => 'Информационный сервис, а не свой автопарк.',
                    'how_items' => [
                        'Найдите местные варианты трансфера',
                        'Сравните доступные предложения',
                        'Запросите информацию о трансфере',
                        'Свяжитесь с местными перевозчиками',
                    ],
                    'footer_descriptor' => 'Связываем путешественников с местными перевозчиками',
                    'footer_note' => 'Transfer Point предоставляет информацию и помогает связаться с перевозчиком. Саму перевозку выполняют независимые компании.',
                ],
                'hero_image' => $this->storePublicImage('images/photos/airport-arrival.png', 'pages/hero/home.png'),
                'meta_title' => 'TRANSFER POINT: ЕВРОПА',
                'meta_description' => 'Связываем путешественников с местными перевозчиками по всей Европе.',
                'meta_keywords' => 'трансфер, Европа, Transfer Point, аэропорт, маршруты',
                'is_published' => true,
            ]
        );

        Page::firstOrCreate(
            ['slug' => 'destinations'],
            [
                'title' => 'TRANSFER POINT: Направления',
                'content' => [
                    'eyebrow' => 'Направления',
                    'heading' => 'Выберите направление',
                    'lede' => 'Локальные страницы отличаются территорией, а не новой дизайн-системой. Меняются знак, география и фото. Бренд остаётся тем же.',
                ],
                'meta_title' => 'TRANSFER POINT: Направления',
                'meta_description' => 'Выберите направление: местные страницы отличаются территорией, а не дизайном.',
                'meta_keywords' => 'направления, Барселона, Андорра, Будапешт, Амстердам, Малага',
                'is_published' => true,
            ]
        );

        Page::firstOrCreate(
            ['slug' => 'information'],
            [
                'title' => 'TRANSFER POINT: Информация',
                'content' => [
                    'eyebrow' => 'Информация',
                    'heading' => 'Связываем путешественников с местными перевозчиками',
                    'lede' => 'Transfer Point — это слой связи для местной информации о трансферах. Это не автопарк, не пул водителей и не оператор поездок.',
                    'say_heading' => 'Как мы говорим',
                    'say_items' => [
                        'Найдите местные варианты трансфера',
                        'Свяжитесь с местными перевозчиками',
                        'Запросите информацию о трансфере',
                        'Сравните доступные предложения',
                        'Независимые перевозчики',
                    ],
                    'avoid_heading' => 'Как мы не говорим',
                    'avoid_items' => [
                        'Наши водители',
                        'Мы вас отвезём',
                        'Наш автопарк',
                        'Забронируйте наш трансфер',
                        'Мы гарантируем поездку',
                    ],
                    'legal_eyebrow' => 'Правовая оговорка',
                    'legal_heading' => 'Знак ЕС: территориальная метка, а не официальная эмблема',
                    'legal_p1' => '<p>Европейскую эмблему могут использовать третьи лица, в том числе в коммерческих целях, если такое использование не создаёт ложного впечатления о связи, поддержке, одобрении или спонсорстве со стороны институтов ЕС.</p>',
                    'legal_p2' => '<p>Transfer Point использует круг ЕС как территориальный знак, а не как охраняемое ядро товарного знака. Мы не используем формулировки вроде «официальный ЕС», «одобрено ЕС» и похожие.</p>',
                    'legal_fineprint' => 'Этот раздел — бренд-рекомендация, а не юридическое заключение по товарным знакам. Источник: административное соглашение с Советом Европы об использовании европейской эмблемы третьими лицами, 2012/C 271/04.',
                ],
                'meta_title' => 'TRANSFER POINT: Информация',
                'meta_description' => 'Связываем путешественников с местными перевозчиками по всей Европе.',
                'meta_keywords' => 'Transfer Point, информация, независимые перевозчики, ЕС',
                'is_published' => true,
            ]
        );

        Page::firstOrCreate(
            ['slug' => 'contacts'],
            [
                'title' => 'TRANSFER POINT: Контакты',
                'content' => [
                    'eyebrow' => 'Контакты',
                    'heading' => 'Свяжитесь с нами',
                    'lede' => 'Задайте вопрос по маршруту или трансферу — мы свяжем вас с независимыми местными перевозчиками.',
                    'email' => 'hello@transferpoint.eu',
                    'phone' => '+34 600 000 000',
                    'whatsapp' => '+34600000000',
                    'telegram' => 'transferpoint',
                    'address' => 'Barcelona, Catalonia, Spain',
                    'extra_text' => 'Transfer Point предоставляет информацию и помогает связаться с перевозчиком. Саму перевозку выполняют независимые компании.',
                    'button_text' => 'Задать вопрос',
                    'map_embed' => '<iframe src="https://www.openstreetmap.org/export/embed.html?bbox=2.141%2C41.372%2C2.198%2C41.403&layer=mapnik"></iframe>',
                ],
                'meta_title' => 'TRANSFER POINT: Контакты',
                'meta_description' => 'Контакты Transfer Point: email, телефон, WhatsApp и Telegram. Задайте вопрос о трансфере в Европе.',
                'meta_keywords' => 'контакты, Transfer Point, трансфер, WhatsApp, Telegram',
                'is_published' => true,
            ]
        );
    }

    private function storePublicImage(string $fromPublic, string $to): string
    {
        $source = public_path($fromPublic);

        if (! is_file($source)) {
            return $fromPublic;
        }

        if (! Storage::disk('public')->exists($to)) {
            Storage::disk('public')->put($to, file_get_contents($source));
        }

        return $to;
    }
}
