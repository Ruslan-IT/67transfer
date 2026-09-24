<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class SiteController extends Controller
{
    public function home(): View
    {
        $page = $this->page('home');

        return view('pages.home', [
            'page' => $page,
            'destinations' => $this->publishedDestinations(),
            'title' => $page->seoTitle(),
            'description' => $page->seoDescription(),
            'keywords' => $page->seoKeywords(),
        ]);
    }

    public function destinations(): View
    {
        $page = $this->page('destinations');

        return view('pages.destinations', [
            'page' => $page,
            'destinations' => $this->publishedDestinations(),
            'title' => $page->seoTitle(),
            'description' => $page->seoDescription(),
            'keywords' => $page->seoKeywords(),
        ]);
    }

    public function information(): View
    {
        $page = $this->page('information');

        return view('pages.information', [
            'page' => $page,
            'title' => $page->seoTitle(),
            'description' => $page->seoDescription(),
            'keywords' => $page->seoKeywords(),
        ]);
    }

    public function contacts(): View
    {
        $page = $this->page('contacts');

        return view('pages.contacts', [
            'page' => $page,
            'title' => $page->seoTitle(),
            'description' => $page->seoDescription(),
            'keywords' => $page->seoKeywords(),
        ]);
    }

    public function contactRequest(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'contact' => ['required', 'string', 'max:180', function (string $attribute, mixed $value, \Closure $fail): void {
                $value = is_string($value) ? $value : '';
                $isEmail = filter_var($value, FILTER_VALIDATE_EMAIL);
                $digits = preg_replace('/\D+/', '', $value) ?? '';

                if (! $isEmail && strlen($digits) < 6) {
                    $fail('Укажите email или телефон.');
                }
            }],
            'message' => ['required', 'string', 'max:2000'],
        ], [
            'name.required' => 'Укажите имя.',
            'contact.required' => 'Укажите email или телефон.',
            'message.required' => 'Напишите вопрос.',
        ]);

        $body = "Имя: {$validated['name']}\n"
            ."Контакт: {$validated['contact']}\n\n"
            ."Сообщение: {$validated['message']}";

        $this->sendSiteMail('Вопрос с сайта Transfer Point', $body, $validated['contact']);

        logger()->info('Contact form request', $validated);

        $status = 'Сообщение успешно отправлено';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => $status,
            ]);
        }

        return back()->with('status', $status);
    }

    public function destination(Destination $destination): View
    {
        return view('pages.destination', [
            'destination' => $destination,
            'title' => $destination->seoTitle(),
            'description' => $destination->seoDescription(),
            'keywords' => $destination->seoKeywords(),
        ]);
    }

    public function request(Request $request, Destination $destination): RedirectResponse
    {
        $validated = $request->validate([
            'from' => ['required', 'string', 'max:180'],
            'to' => ['required', 'string', 'max:180'],
            'date' => ['required', 'date'],
            'email' => ['required', 'email', 'max:180'],
        ]);

        $body = "Новый запрос на трансфер\n\n"
            ."Откуда: {$validated['from']}\n"
            ."Куда: {$validated['to']}\n"
            ."Дата: {$validated['date']}\n"
            ."Email: {$validated['email']}";

        $this->sendSiteMail('Новый запрос на трансфер', $body, $validated['email']);

        logger()->info('Transfer information request', [
            'destination' => $destination->slug,
            ...$validated,
        ]);

        return back()->with('status', 'Запрос получен. Мы свяжем вас с независимыми местными перевозчиками.');
    }

    private function sendSiteMail(string $subject, string $body, ?string $replyTo = null): void
    {
        $to = config('mail.to.address');

        if (! is_string($to) || $to === '') {
            return;
        }

        Mail::raw($body, function ($mail) use ($to, $subject, $replyTo): void {
            $mail->to($to)->subject($subject);

            if (is_string($replyTo) && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                $mail->replyTo($replyTo);
            }
        });
    }

    private function page(string $slug): Page
    {
        $page = Page::query()->where('slug', $slug)->published()->first();

        abort_unless($page instanceof Page, 404);

        return $page;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Destination>
     */
    private function publishedDestinations()
    {
        return Destination::query()->published()->ordered()->get();
    }
}
