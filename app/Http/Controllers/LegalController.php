<?php

namespace App\Http\Controllers;

use App\Models\LegalDocument;
use App\Support\CookieConsent;
use App\Support\LegalDocuments;
use Illuminate\View\View;

class LegalController extends Controller
{
    public function privacy(): View
    {
        return $this->show(LegalDocuments::PRIVACY);
    }

    public function consent(): View
    {
        return $this->show(LegalDocuments::CONSENT);
    }

    private function show(string $key): View
    {
        $document = LegalDocument::published($key);
        abort_unless($document instanceof LegalDocument, 404);

        return view('pages.legal', [
            'document' => $document,
            'operator' => app(CookieConsent::class)->operator(),
            'title' => $document->seoTitle(),
            'description' => $document->seoDescription(),
        ]);
    }
}
