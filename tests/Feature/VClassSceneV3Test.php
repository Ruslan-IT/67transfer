<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VClassSceneV3Test extends TestCase
{
    use RefreshDatabase;

    public function test_v3_scene_page_is_isolated_and_not_indexed(): void
    {
        $response = $this->get(route('demo.v-class-scene-v3'))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertSee('data-scene', false)
            ->assertSee('js/v-class-scene-v3.js', false)
            ->assertSee('css/v-class-scene-v3.css', false)
            ->assertSee('Прилёт в аэропорт')
            ->assertSee('Готовы к поездке')
            ->assertSee('Комфортный трансфер до места назначения');

        $html = $response->getContent();
        $this->assertStringNotContainsString('js/v-class-scene-v2.js', $html);
        $this->assertStringNotContainsString('media/v-class-scene-v2/', $html);
        $this->assertStringNotContainsString('mc.yandex.ru', $html);
        $this->assertGreaterThanOrEqual(4, substr_count($html, 'data-scene-video'));
    }
}
