<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(): View
    {
        $posts = BlogPost::query()
            ->published()
            ->latestPublished()
            ->paginate(4);

        return view('pages.blog.index', [
            'posts' => $posts,
            'title' => 'TRANSFER POINT: Блог',
            'description' => 'Статьи о трансферах, маршрутах и местном транспорте в Европе.',
            'keywords' => 'блог, трансфер, Европа, Transfer Point',
        ]);
    }

    public function show(BlogPost $post): View
    {
        return view('pages.blog.show', [
            'post' => $post,
            'title' => $post->seoTitle(),
            'description' => $post->seoDescription(),
            'keywords' => $post->seoKeywords(),
        ]);
    }
}
