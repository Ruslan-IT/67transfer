<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\Page;
use Illuminate\Http\Response;

class HomeThreeController extends Controller
{
    public function __invoke(): Response
    {
        $page = Page::query()->where('slug', 'home')->published()->first();

        abort_unless($page instanceof Page, 404);

        $draft = session('transfer.draft');

        return response()
            ->view('pages.home-3', [
                'page' => $page,
                'destinations' => Destination::query()->published()->ordered()->get(),
                'title' => $page->seoTitle(),
                'description' => $page->seoDescription(),
                'keywords' => $page->seoKeywords(),
                'draft' => is_array($draft) ? $draft : null,
            ])
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
