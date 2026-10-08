<?php

namespace App\Http\Controllers;

use App\Models\CaseNote;
use App\Models\LegalCase;
use Illuminate\Http\Request;

class CaseNoteController extends Controller
{
    public function store(Request $request, LegalCase $case)
    {
        $this->authorize('view', $case);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:10000'],
            'is_pinned' => ['nullable', 'boolean'],
        ]);

        $case->notes()->create([
            'body' => $data['body'],
            'is_pinned' => $request->boolean('is_pinned'),
            'user_id' => $request->user()->id,
        ]);

        return redirect()->to(route('cases.show', $case).'#notas')->with('success', 'Nota agregada.');
    }

    public function update(Request $request, CaseNote $note)
    {
        $this->authorize('update', $note);

        $data = $request->validate(['body' => ['required', 'string', 'max:10000']]);
        $note->update($data);

        return redirect()->to(route('cases.show', $note->legal_case_id).'#notas')->with('success', 'Nota actualizada.');
    }

    public function pin(CaseNote $note)
    {
        $this->authorize('view', $note->legalCase);
        $note->update(['is_pinned' => ! $note->is_pinned]);

        return redirect()->to(route('cases.show', $note->legal_case_id).'#notas');
    }

    public function destroy(CaseNote $note)
    {
        $this->authorize('delete', $note);
        $note->delete();

        return redirect()->to(route('cases.show', $note->legal_case_id).'#notas')->with('success', 'Nota eliminada.');
    }
}
