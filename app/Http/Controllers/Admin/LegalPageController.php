<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LegalPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LegalPageController extends Controller
{
    /**
     * Balises conservées à l'enregistrement. Tout le reste est retiré.
     */
    private const ALLOWED_TAGS = '<p><br><strong><b><em><i><u><s><h2><h3><ul><ol><li><a><blockquote>';

    public function index(): Response
    {
        return Inertia::render('Admin/LegalPages', [
            'pages' => LegalPage::orderBy('id')->get(['id', 'slug', 'title', 'content', 'is_published', 'updated_at']),
        ]);
    }

    public function update(Request $request, LegalPage $legalPage): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'content' => ['nullable', 'string', 'max:120000'],
            'is_published' => ['boolean'],
        ]);

        $legalPage->update([
            'title' => $data['title'],
            'content' => $this->sanitize($data['content'] ?? ''),
            'is_published' => $data['is_published'] ?? true,
        ]);

        return back()->with('success', 'Page enregistrée.');
    }

    /**
     * Nettoyage du HTML avant stockage.
     *
     * Le contenu vient d'un administrateur, pas du public, mais il est affiché
     * via v-html : on retire donc les balises hors liste, les gestionnaires
     * d'évènements et les liens javascript. Ce n'est pas un assainisseur
     * complet — si le contenu devait un jour venir d'ailleurs que de l'admin,
     * il faudrait passer par une bibliothèque dédiée comme HTMLPurifier.
     */
    private function sanitize(string $html): string
    {
        $clean = strip_tags($html, self::ALLOWED_TAGS);

        // Retire tout attribut evenementiel (onclick, onerror, etc.) et les styles inline.
        $clean = preg_replace('/\s+(on\w+|style|class|id)\s*=\s*"[^"]*"/i', '', $clean);
        $clean = preg_replace("/\s+(on\w+|style|class|id)\s*=\s*'[^']*'/i", '', $clean);

        // Neutralise les liens javascript: et data:.
        $clean = preg_replace('/href\s*=\s*(["\'])\s*(javascript|data):[^"\']*\1/i', 'href="#"', $clean);

        return trim((string) $clean);
    }
}
