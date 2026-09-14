<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Transaction;
use App\Services\DocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(private DocumentService $documents)
    {
    }

    public function store(Request $request, Transaction $transaction): RedirectResponse
    {
        abort_unless($transaction->user_id === $request->user()->id, 403);
        abort_unless($request->user()->can('manage transactions'), 403);

        $request->validate([
            'documents' => ['required', 'array', 'max:10'],
            'documents.*' => ['file', 'max:8192', 'mimes:pdf,jpg,jpeg,png,webp,heic'],
        ]);

        $this->documents->attachMany($transaction, $request->file('documents'), $request->user()->id);

        return back()->with('success', 'Document ajoute.');
    }

    /**
     * Les justificatifs ne sont jamais servis depuis public/ : ils passent par
     * cette route, qui verifie que le fichier appartient bien a l'utilisateur.
     */
    public function download(Request $request, Document $document): StreamedResponse
    {
        abort_unless($document->user_id === $request->user()->id, 403);
        abort_unless(Storage::disk($document->disk)->exists($document->path), 404);

        return Storage::disk($document->disk)->download($document->path, $document->original_name);
    }

    public function destroy(Request $request, Document $document): RedirectResponse
    {
        abort_unless($document->user_id === $request->user()->id, 403);
        abort_unless($request->user()->can('manage transactions'), 403);

        $document->delete();

        return back()->with('success', 'Document supprime.');
    }
}
