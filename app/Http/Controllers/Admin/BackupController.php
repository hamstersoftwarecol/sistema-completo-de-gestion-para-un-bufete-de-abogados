<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BackupService;

class BackupController extends Controller
{
    public function __construct(private BackupService $backups) {}

    public function store()
    {
        try {
            $name = $this->backups->create();
        } catch (\Throwable $e) {
            report($e);

            return redirect()->to(route('admin.settings.edit').'#respaldos')->with('error', 'No se pudo crear la copia: '.$e->getMessage());
        }

        return redirect()->to(route('admin.settings.edit').'#respaldos')->with('success', "Copia de seguridad {$name} creada.");
    }

    public function download(string $name)
    {
        return response()->download($this->backups->path($name));
    }

    public function destroy(string $name)
    {
        $this->backups->delete($name);

        return redirect()->to(route('admin.settings.edit').'#respaldos')->with('success', 'Copia de seguridad eliminada.');
    }
}
