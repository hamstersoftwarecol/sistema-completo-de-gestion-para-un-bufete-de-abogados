<?php

namespace App\Http\Controllers;

use App\Models\CaseParty;
use App\Models\LegalCase;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CasePartyController extends Controller
{
    public function store(Request $request, LegalCase $case)
    {
        $this->authorize('update', $case);
        $case->parties()->create($this->validated($request));

        return redirect()->to(route('cases.show', $case).'#partes')->with('success', 'Parte agregada al caso.');
    }

    public function update(Request $request, CaseParty $party)
    {
        $this->authorize('update', $party->legalCase);
        $party->update($this->validated($request));

        return redirect()->to(route('cases.show', $party->legal_case_id).'#partes')->with('success', 'Parte actualizada.');
    }

    public function destroy(CaseParty $party)
    {
        $this->authorize('update', $party->legalCase);
        $party->delete();

        return redirect()->to(route('cases.show', $party->legal_case_id).'#partes')->with('success', 'Parte eliminada.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', Rule::in(array_keys(config('bufete.party_roles')))],
            'document_number' => ['nullable', 'string', 'max:40'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'lawyer_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
