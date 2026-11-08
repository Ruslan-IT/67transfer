<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VClassSceneTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_scene_page_is_isolated_and_not_indexed(): void
    {
        $response = $this->get(route('demo.v-class-scene'))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertSee('Ваше путешествие начинается здесь')
            ->assertSee('data-scene', false)
            ->assertSee('ScrollTrigger.min.js', false)
            ->assertSee('js/v-class-scene.js', false);

        $html = $response->getContent();
        $this->assertStringNotContainsString('mc.yandex.ru', $html);
        $this->assertStringNotContainsString('transfer-search', $html);
    }
}
