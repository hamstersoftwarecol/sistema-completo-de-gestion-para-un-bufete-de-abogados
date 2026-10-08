<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\LegalCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Documentos del caso (PDF, imágenes, video, audio, ofimática). Se guardan en
 * almacenamiento privado y sólo se sirven a usuarios autorizados.
 */
class DocumentController extends Controller
{
    public function store(Request $request, LegalCase $case)
    {
        $this->authorize('update', $case);

        $request->validate([
            'documents' => ['required', 'array', 'max:10'],
            'documents.*' => ['file', 'max:'.config('bufete.upload_max_kb'), 'mimes:'.config('bufete.document_mimes')],
            'title' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', Rule::in(array_keys(config('bufete.document_categories')))],
        ], [
            'documents.required' => 'Seleccione al menos un archivo.',
            'documents.*.max' => 'Cada archivo puede pesar como máximo '.round(config('bufete.upload_max_kb') / 1024).' MB.',
            'documents.*.mimes' => 'Tipo de archivo no permitido.',
        ]);

        $count = self::storeUploads($request, $case);

        return redirect()->to(route('cases.show', $case).'#documentos')
            ->with('success', $count === 1 ? 'Documento cargado.' : "{$count} documentos cargados.");
    }

    /**
     * Guarda los archivos del campo documents[] para el caso.
     */
    public static function storeUploads(Request $request, LegalCase $case): int
    {
        $files = $request->file('documents', []);
        $count = 0;

        foreach ($files as $file) {
            $name = Str::uuid().'.'.strtolower($file->getClientOriginalExtension() ?: $file->extension());
            $path = $file->storeAs("documents/{$case->id}", $name, 'local');

            $title = $request->input('title');
            if (blank($title) || count($files) > 1) {
                $title = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            }

            $case->documents()->create([
                'user_id' => $request->user()->id,
                'title' => Str::limit($title, 250, ''),
                'category' => $request->input('category') ?: self::guessCategory($file->getMimeType()),
                'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
            $count++;
        }

        return $count;
    }

    private static function guessCategory(?string $mime): string
    {
        return str_starts_with((string) $mime, 'video/') || str_starts_with((string) $mime, 'audio/') ? 'multimedia' : 'otro';
    }

    /** Vista previa en el navegador (PDF, imagen, video, audio). */
    public function show(Document $document)
    {
        $this->authorize('view', $document);
        abort_unless(Storage::disk('local')->exists($document->path), 404, 'El archivo no se encuentra en el servidor.');

        if (! $document->isPreviewable()) {
            return $this->download($document);
        }

        return response()->file(Storage::disk('local')->path($document->path), [
            'Content-Type' => $document->mime_type ?? 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.Str::ascii($document->original_name).'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function download(Document $document)
    {
        $this->authorize('view', $document);
        abort_unless(Storage::disk('local')->exists($document->path), 404, 'El archivo no se encuentra en el servidor.');

        return Storage::disk('local')->download($document->path, $document->original_name);
    }

    public function destroy(Document $document)
    {
        $this->authorize('delete', $document);

        Storage::disk('local')->delete($document->path);
        $caseId = $document->legal_case_id;
        $document->delete();

        return redirect()->to(route('cases.show', $caseId).'#documentos')->with('success', 'Documento eliminado.');
    }
}
