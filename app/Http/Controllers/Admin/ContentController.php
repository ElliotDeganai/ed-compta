<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContentController extends Controller
{
    /**
     * Liste blanche des balises conservées à l'enregistrement.
     * Elle doit rester alignée sur les outils proposés par RichText.vue :
     * un bouton dont la balise manque ici verrait sa mise en forme disparaître
     * silencieusement à la sauvegarde.
     */
    private const ALLOWED_TAGS = '<p><br><hr><strong><b><em><i><u><s><strike><h2><h3><ul><ol><li><a><blockquote>';

    public function index(): Response
    {
        return Inertia::render('Admin/Content', [
            'pages' => Page::orderBy('position')
                ->orderBy('id')
                ->get(['id', 'slug', 'title', 'meta_description', 'content', 'is_published', 'updated_at']),
        ]);
    }

    public function updatePage(Request $request, Page $page): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:120000'],
            'is_published' => ['boolean'],
        ]);

        $page->update([
            'title' => $data['title'],
            'meta_description' => $data['meta_description'] ?? null,
            'content' => $this->sanitize($data['content'] ?? ''),
            'is_published' => $data['is_published'] ?? true,
        ]);

        return back()->with('success', 'Page enregistrée.');
    }

    /**
     * Le contenu est rendu par v-html cote client : tout script stocke
     * s'executerait. On retire donc les balises hors liste, les attributs
     * evenementiels et les styles inline, et on neutralise les liens
     * javascript: et data:.
     *
     * Ce filtre couvre le cas reel, un contenu saisi depuis l'administration.
     * Si la saisie devait un jour venir d'ailleurs, il faudrait HTMLPurifier.
     */
    private function sanitize(string $html): string
    {
        $clean = strip_tags($html, self::ALLOWED_TAGS);

        $clean = preg_replace('/\s+(on\w+|style|class|id)\s*=\s*"[^"]*"/i', '', $clean);
        $clean = preg_replace("/\s+(on\w+|style|class|id)\s*=\s*'[^']*'/i", '', $clean);
        $clean = preg_replace('/href\s*=\s*(["\'])\s*(javascript|data|vbscript):[^"\']*\1/i', 'href="#"', $clean);

        return trim((string) $clean);
    }
}
