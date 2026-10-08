<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Restablece la base de datos con datos de demostración (también lo usa setup.php).
 */
class ResetDemo extends Command
{
    protected $signature = 'bufete:demo {--force : No pedir confirmación}';

    protected $description = 'Borra TODOS los datos y carga de nuevo los datos de demostración';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('Se eliminarán todos los datos actuales. ¿Continuar?')) {
            return self::FAILURE;
        }

        // Archivos subidos (documentos, soportes de gastos, logotipo).
        foreach (['documents', 'expenses', 'branding'] as $dir) {
            File::deleteDirectory(storage_path('app/private/'.$dir));
        }

        $this->call('migrate:fresh', ['--seed' => true, '--force' => true]);
        $this->call('optimize:clear');

        $this->newLine();
        $this->info('Datos de demostración cargados. Usuario: admin@bufete.test / Contraseña: password');

        return self::SUCCESS;
    }
}
