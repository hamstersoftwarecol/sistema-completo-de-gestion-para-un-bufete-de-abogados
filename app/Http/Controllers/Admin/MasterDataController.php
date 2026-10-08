<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CaseStatus;
use App\Models\CaseType;
use App\Models\Court;
use App\Support\Badge;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Datos maestros: tipos de caso, estados de caso y juzgados/tribunales.
 */
class MasterDataController extends Controller
{
    public function index()
    {
        return view('admin.master-data.index', [
            'types' => CaseType::query()->withCount('cases')->orderBy('name')->get(),
            'statuses' => CaseStatus::query()->withCount('cases')->ordered()->get(),
            'courts' => Court::query()->withCount(['cases', 'hearings'])->orderBy('name')->get(),
            'colors' => Badge::names(),
        ]);
    }

    // --- Tipos de caso ---------------------------------------------------

    public function storeType(Request $request)
    {
        CaseType::create($this->typeData($request));

        return $this->back('tipos', 'Tipo de caso creado.');
    }

    public function updateType(Request $request, CaseType $type)
    {
        $type->update($this->typeData($request, $type));

        return $this->back('tipos', 'Tipo de caso actualizado.');
    }

    public function destroyType(CaseType $type)
    {
        if ($type->cases()->exists()) {
            return $this->back('tipos', "No se puede eliminar «{$type->name}»: está asignado a {$type->cases()->count()} caso(s). Puede desactivarlo.", 'error');
        }
        $type->delete();

        return $this->back('tipos', 'Tipo de caso eliminado.');
    }

    private function typeData(Request $request, ?CaseType $type = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('case_types')->ignore($type?->id)],
            'description' => ['nullable', 'string', 'max:255'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    // --- Estados de caso -------------------------------------------------

    public function storeStatus(Request $request)
    {
        CaseStatus::create($this->statusData($request));

        return $this->back('estados', 'Estado creado.');
    }

    public function updateStatus(Request $request, CaseStatus $status)
    {
        $status->update($this->statusData($request, $status));

        return $this->back('estados', 'Estado actualizado.');
    }

    public function destroyStatus(CaseStatus $status)
    {
        if ($status->cases()->exists()) {
            return $this->back('estados', "No se puede eliminar «{$status->name}»: está asignado a {$status->cases()->count()} caso(s).", 'error');
        }
        $status->delete();

        return $this->back('estados', 'Estado eliminado.');
    }

    private function statusData(Request $request, ?CaseStatus $status = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('case_statuses')->ignore($status?->id)],
            'color' => ['required', Rule::in(Badge::names())],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);
        $data['is_closed'] = $request->boolean('is_closed');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }

    // --- Juzgados / tribunales -------------------------------------------

    public function storeCourt(Request $request)
    {
        Court::create($this->courtData($request));

        return $this->back('juzgados', 'Juzgado creado.');
    }

    public function updateCourt(Request $request, Court $court)
    {
        $court->update($this->courtData($request));

        return $this->back('juzgados', 'Juzgado actualizado.');
    }

    public function destroyCourt(Court $court)
    {
        if ($court->cases()->exists()) {
            return $this->back('juzgados', "No se puede eliminar «{$court->name}»: tiene {$court->cases()->count()} caso(s) vinculados.", 'error');
        }
        $court->delete();

        return $this->back('juzgados', 'Juzgado eliminado.');
    }

    private function courtData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);
    }

    private function back(string $tab, string $message, string $type = 'success')
    {
        return redirect()->to(route('admin.master-data.index').'#'.$tab)->with($type, $message);
    }
}
