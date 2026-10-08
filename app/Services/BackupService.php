<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use ZipArchive;

/**
 * Copias de seguridad: base de datos SQLite + archivos subidos, en un ZIP.
 */
class BackupService
{
    public function directory(): string
    {
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);

        return $dir;
    }

    public function create(): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('La extensión PHP "zip" no está habilitada (actívela en php.ini).');
        }

        $name = 'respaldo-'.now()->format('Y-m-d_His').'.zip';
        $zipPath = $this->directory().DIRECTORY_SEPARATOR.$name;
        $dbCopy = $this->directory().DIRECTORY_SEPARATOR.'tmp-'.uniqid().'.sqlite';

        $this->copyDatabase($dbCopy);

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo crear el archivo ZIP.');
        }

        $zip->addFile($dbCopy, 'database/database.sqlite');

        $files = storage_path('app/private');
        if (is_dir($files)) {
            foreach (File::allFiles($files) as $file) {
                $zip->addFile($file->getPathname(), 'storage/app/private/'.str_replace('\\', '/', $file->getRelativePathname()));
            }
        }

        $zip->addFromString('LEEME.txt', implode("\n", [
            'Copia de seguridad de '.config('app.name'),
            'Fecha: '.now()->format('d/m/Y H:i:s'),
            '',
            'Para restaurar:',
            '1. Copie database/database.sqlite sobre el archivo database/database.sqlite del proyecto.',
            '2. Copie la carpeta storage/app/private sobre la del proyecto.',
            '3. Use la misma APP_KEY del archivo .env (necesaria para descifrar las claves de IA).',
        ]));

        $zip->close();
        @unlink($dbCopy);

        return $name;
    }

    /**
     * Copia consistente de la base de datos (VACUUM INTO), con respaldo a copy().
     */
    protected function copyDatabase(string $target): void
    {
        $source = DB::connection()->getDatabaseName();

        try {
            DB::statement('VACUUM INTO ?', [$target]);
        } catch (\Throwable) {
            if (! @copy($source, $target)) {
                throw new RuntimeException('No se pudo copiar la base de datos.');
            }
        }
    }

    /**
     * @return array<int,array{name:string,size:int,date:Carbon}>
     */
    public function list(): array
    {
        return collect(File::files($this->directory()))
            ->filter(fn ($f) => $f->getExtension() === 'zip')
            ->map(fn ($f) => [
                'name' => $f->getFilename(),
                'size' => $f->getSize(),
                'date' => Carbon::createFromTimestamp($f->getMTime()),
            ])
            ->sortByDesc('date')
            ->values()
            ->all();
    }

    public function path(string $name): string
    {
        if (! preg_match('/^respaldo-[\d_\-]+\.zip$/', $name)) {
            abort(404);
        }

        $path = $this->directory().DIRECTORY_SEPARATOR.$name;
        abort_unless(is_file($path), 404);

        return $path;
    }

    public function delete(string $name): void
    {
        @unlink($this->path($name));
    }
}
