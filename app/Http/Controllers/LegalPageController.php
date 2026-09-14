<?php

namespace App\Http\Controllers;

use App\Models\LegalPage;
use Inertia\Inertia;
use Inertia\Response;

class LegalPageController extends Controller
{
    public function show(LegalPage $legalPage): Response
    {
        abort_unless($legalPage->is_published, 404);

        return Inertia::render('Legal', [
            'page' => [
                'title' => $legalPage->title,
                'content' => $legalPage->content,
                'updated_at' => $legalPage->updated_at?->toDateString(),
            ],
        ]);
    }
}
