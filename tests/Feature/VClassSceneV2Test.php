<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VClassSceneV2Test extends TestCase
{
    use RefreshDatabase;

    public function test_v2_scene_page_is_isolated_and_not_indexed(): void
    {
        $response = $this->get(route('demo.v-class-scene-v2'))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertSee('data-scene', false)
            ->assertSee('js/v-class-scene-v2.js', false)
            ->assertSee('css/v-class-scene-v2.css', false)
            ->assertSee('Прилёт в аэропорт')
            ->assertSee('Комфортный трансфер до места назначения');

        $html = $response->getContent();
        $this->assertStringNotContainsString('js/v-class-scene.js', $html);
        $this->assertStringNotContainsString('media/v-class-scene/', $html);
        $this->assertStringNotContainsString('mc.yandex.ru', $html);
        $this->assertSame(7, substr_count($html, 'data-scene-video'));
    }
}
