<?php

namespace Database\Seeders;

use App\Models\Destination;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DestinationSeeder extends Seeder
{
    public function run(): void
    {
        $destinations = [
            [
                'name' => 'Barcelona',
                'slug' => 'barcelona',
                'title' => 'Найдите вариант трансфера в Барселоне',
                'content' => '<p>Сравните доступные варианты и свяжитесь с независимыми перевозчиками: аэропорт Барселоны, центр города и маршруты Коста-Бравы.</p>',
                'image_source' => 'images/photos/airport-arrival.png',
                'image_target' => 'destinations/barcelona.png',
                'image_alt' => 'Прилёт и трансфер в Барселоне',
                'badge_source' => 'images/badges/barcelona.png',
                'badge_target' => 'destinations/badges/barcelona.png',
                'territory' => 'Каталония',
                'tagline' => 'Аэропорт · город · маршруты Коста-Бравы',
                'from_default' => 'Аэропорт Барселоны (BCN)',
                'to_default' => 'Центр Барселоны',
                'routes' => [
                    'Аэропорт Барселоны (BCN) → центр города',
                    'Аэропорт Барселоны (BCN) → Коста-Брава',
                    'Центр города → курорты Коста-Бравы',
                ],
                'sort_order' => 1,
                'meta_title' => 'TRANSFER POINT: Барселона — трансферы и маршруты',
                'meta_description' => 'Информация о трансферах в Барселоне: аэропорт BCN, центр города и Коста-Брава. Связь с независимыми перевозчиками.',
                'meta_keywords' => 'Барселона, трансфер, аэропорт BCN, Коста-Брава',
            ],
            [
                'name' => 'Andorra',
                'slug' => 'andorra',
                'title' => 'Найдите вариант трансфера в Андорре',
                'content' => '<p>Запросите информацию о трансфере по горным и курортным маршрутам — мы свяжем вас с независимыми местными перевозчиками.</p>',
                'image_source' => 'images/photos/resort-route.png',
                'image_target' => 'destinations/andorra.png',
                'image_alt' => 'Горный курортный маршрут в Андорре',
                'badge_source' => 'images/badges/andorra.png',
                'badge_target' => 'destinations/badges/andorra.png',
                'territory' => 'Андорра',
                'tagline' => 'Аэропорт · город · курортные маршруты',
                'from_default' => 'Аэропорт Барселоны (BCN)',
                'to_default' => 'Андорра-ла-Велья',
                'routes' => [
                    'Аэропорт Барселоны (BCN) → Андорра',
                    'Аэропорт Тулузы (TLS) → Андорра',
                    'Андорра-ла-Велья → горнолыжные курорты',
                ],
                'sort_order' => 2,
                'meta_title' => 'TRANSFER POINT: Андорра — трансферы и курорты',
                'meta_description' => 'Трансферы в Андорру из Барселоны и Тулузы, маршруты к горнолыжным курортам. Независимые местные перевозчики.',
                'meta_keywords' => 'Андорра, трансфер, Барселона, Тулуза, горнолыжные курорты',
            ],
            [
                'name' => 'Budapest',
                'slug' => 'budapest',
                'title' => 'Найдите вариант трансфера в Будапеште',
                'content' => '<p>Подберите местные варианты трансфера между аэропортом Будапешта, городом и ближайшими направлениями. Перевозку выполняют независимые компании.</p>',
                'image_source' => 'images/photos/city-destination.png',
                'image_target' => 'destinations/budapest.png',
                'image_alt' => 'Городской трансфер вдоль Дуная в Будапеште',
                'badge_source' => 'images/badges/budapest.png',
                'badge_target' => 'destinations/badges/budapest.png',
                'territory' => 'Венгрия',
                'tagline' => 'Аэропорт · город · курортные маршруты',
                'from_default' => 'Аэропорт Будапешта (BUD)',
                'to_default' => 'Центр Будапешта',
                'routes' => [
                    'Аэропорт Будапешта (BUD) → центр города',
                    'Центр города → термальные курорты',
                    'Аэропорт → озеро Балатон',
                ],
                'sort_order' => 3,
                'meta_title' => 'TRANSFER POINT: Будапешт — трансферы по городу',
                'meta_description' => 'Трансферы в Будапеште: аэропорт BUD, центр города, термальные курорты и Балатон. Связь с независимыми перевозчиками.',
                'meta_keywords' => 'Будапешт, трансфер, аэропорт BUD, Балатон',
            ],
            [
                'name' => 'Amsterdam',
                'slug' => 'amsterdam',
                'title' => 'Найдите вариант трансфера в Амстердаме',
                'content' => '<p>Запросите информацию о трансфере из Схипхола, в центр города и по региональным маршрутам — затем свяжитесь с независимыми перевозчиками.</p>',
                'image_source' => 'images/photos/city-destination.png',
                'image_target' => 'destinations/amsterdam.png',
                'image_alt' => 'Городской трансфер в Европе',
                'badge_source' => 'images/badges/amsterdam.png',
                'badge_target' => 'destinations/badges/amsterdam.png',
                'territory' => 'Нидерланды',
                'tagline' => 'Аэропорт · город · курортные маршруты',
                'from_default' => 'Аэропорт Амстердама Схипхол (AMS)',
                'to_default' => 'Центр Амстердама',
                'routes' => [
                    'Схипхол (AMS) → центр Амстердама',
                    'Амстердам → Гаага',
                    'Амстердам → Роттердам',
                ],
                'sort_order' => 4,
                'meta_title' => 'TRANSFER POINT: Амстердам — трансферы из Схипхола',
                'meta_description' => 'Трансферы в Амстердаме: Схипхол AMS, центр города, Гаага и Роттердам. Независимые перевозчики.',
                'meta_keywords' => 'Амстердам, трансфер, Схипхол, AMS, Гаага, Роттердам',
            ],
            [
                'name' => 'Malaga',
                'slug' => 'malaga',
                'title' => 'Найдите вариант трансфера в Малаге',
                'content' => '<p>Сравните доступные варианты для аэропорта Малаги, города и курортов Коста-дель-Соль с независимыми местными перевозчиками.</p>',
                'image_source' => 'images/photos/airport-arrival.png',
                'image_target' => 'destinations/malaga.png',
                'image_alt' => 'Трансфер из аэропорта на Коста-дель-Соль',
                'badge_source' => 'images/badges/malaga.png',
                'badge_target' => 'destinations/badges/malaga.png',
                'territory' => 'Испания',
                'tagline' => 'Аэропорт · город · маршруты Коста-дель-Соль',
                'from_default' => 'Аэропорт Малаги (AGP)',
                'to_default' => 'Центр Малаги',
                'routes' => [
                    'Аэропорт Малаги (AGP) → центр города',
                    'Аэропорт Малаги (AGP) → Марбелья',
                    'Центр города → курорты Коста-дель-Соль',
                ],
                'sort_order' => 5,
                'meta_title' => 'TRANSFER POINT: Малага — трансферы на Коста-дель-Соль',
                'meta_description' => 'Трансферы в Малаге: аэропорт AGP, центр города, Марбелья и Коста-дель-Соль. Связь с независимыми перевозчиками.',
                'meta_keywords' => 'Малага, трансфер, аэропорт AGP, Марбелья, Коста-дель-Соль',
            ],
        ];

        foreach ($destinations as $destination) {
            Destination::firstOrCreate(
                ['slug' => $destination['slug']],
                [
                    'name' => $destination['name'],
                    'title' => $destination['title'],
                    'content' => $destination['content'],
                    'image' => $this->storePublicImage($destination['image_source'], $destination['image_target']),
                    'image_alt' => $destination['image_alt'],
                    'badge' => $this->storePublicImage($destination['badge_source'], $destination['badge_target']),
                    'territory' => $destination['territory'],
                    'tagline' => $destination['tagline'],
                    'from_default' => $destination['from_default'],
                    'to_default' => $destination['to_default'],
                    'routes' => $destination['routes'],
                    'is_published' => true,
                    'sort_order' => $destination['sort_order'],
                    'meta_title' => $destination['meta_title'],
                    'meta_description' => $destination['meta_description'],
                    'meta_keywords' => $destination['meta_keywords'],
                ]
            );
        }
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
