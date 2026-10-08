<?php

/**
 * LexGestión — instalador y restablecimiento de datos de demostración.
 *
 * Consola (recomendado):
 *   php setup.php            Prepara .env, APP_KEY y la base SQLite; instala si hace falta.
 *   php setup.php --reset    Borra TODOS los datos y carga de nuevo los datos de demostración.
 *
 * Navegador (sólo desde el mismo equipo, p. ej. XAMPP):
 *   http://localhost/<carpeta>/setup.php   o   http://localhost/<carpeta>/public/setup.php
 *   Para restablecer una instalación existente se piden las credenciales de un superadministrador.
 *   Se puede desactivar con SETUP_WEB_ENABLED=false en el archivo .env.
 */

define('LEX_ROOT', __DIR__);

const LEX_MIN_PHP = '8.2.0';

$cli = PHP_SAPI === 'cli';
@set_time_limit(300);

// ---------------------------------------------------------------------------
// Utilidades
// ---------------------------------------------------------------------------

/** Lee un valor del archivo .env sin cargar Laravel. */
function lex_env(string $key, ?string $default = null): ?string
{
    $file = LEX_ROOT.'/.env';
    if (! is_file($file)) {
        return $default;
    }
    foreach (file($file, FILE_IGNORE_NEW_LINES) as $line) {
        if (preg_match('/^\s*'.preg_quote($key, '/').'\s*=\s*(.*)\s*$/', $line, $m)) {
            return trim($m[1], " \"'");
        }
    }

    return $default;
}

function lex_bool(?string $value, bool $default): bool
{
    if ($value === null || $value === '') {
        return $default;
    }

    return in_array(strtolower($value), ['1', 'true', 'on', 'yes'], true);
}

/** @return array<string, array{0:bool,1:string,2:bool}> [etiqueta => [ok, detalle, obligatorio]] */
function lex_requirements(): array
{
    $ext = fn (string $e) => extension_loaded($e);

    return [
        'PHP >= '.LEX_MIN_PHP => [version_compare(PHP_VERSION, LEX_MIN_PHP, '>='), PHP_VERSION, true],
        'Extensión pdo_sqlite' => [$ext('pdo_sqlite'), 'Base de datos SQLite', true],
        'Extensión mbstring' => [$ext('mbstring'), 'Textos con tildes y ñ', true],
        'Extensión openssl' => [$ext('openssl'), 'Cifrado', true],
        'Extensión fileinfo' => [$ext('fileinfo'), 'Tipo de los documentos subidos', true],
        'Extensión zip' => [$ext('zip'), 'Copias de seguridad (opcional)', false],
        'Extensión curl' => [$ext('curl'), 'Asistente de IA (recomendado)', false],
        'Dependencias (vendor/)' => [is_file(LEX_ROOT.'/vendor/autoload.php'), 'Ejecute: composer install', true],
        'Recursos compilados (public/build)' => [is_file(LEX_ROOT.'/public/build/manifest.json'), 'Ejecute: npm install && npm run build', true],
        'Carpeta storage/ con escritura' => [is_writable(LEX_ROOT.'/storage'), 'Permisos de escritura', true],
        'Carpeta database/ con escritura' => [is_writable(LEX_ROOT.'/database'), 'Permisos de escritura', true],
    ];
}

function lex_requirements_ok(): bool
{
    foreach (lex_requirements() as [$ok, , $required]) {
        if ($required && ! $ok) {
            return false;
        }
    }

    return true;
}

/** Crea .env, APP_KEY, la base de datos y las carpetas necesarias. */
function lex_prepare(array &$log): void
{
    if (! is_file(LEX_ROOT.'/.env')) {
        copy(LEX_ROOT.'/.env.example', LEX_ROOT.'/.env');
        $log[] = 'Archivo .env creado a partir de .env.example.';
    }

    $env = file_get_contents(LEX_ROOT.'/.env');
    if (! preg_match('/^APP_KEY=\S+/m', $env)) {
        $key = 'base64:'.base64_encode(random_bytes(32));
        $env = preg_match('/^APP_KEY=.*$/m', $env)
            ? preg_replace('/^APP_KEY=.*$/m', 'APP_KEY='.$key, $env)
            : "APP_KEY={$key}\n".$env;
        file_put_contents(LEX_ROOT.'/.env', $env);
        $log[] = 'Clave de la aplicación (APP_KEY) generada.';
    }

    $db = LEX_ROOT.'/database/database.sqlite';
    if (! is_file($db)) {
        touch($db);
        $log[] = 'Base de datos SQLite creada en database/database.sqlite.';
    }

    foreach (['storage/app/private', 'storage/app/public', 'storage/framework/cache/data', 'storage/framework/sessions',
        'storage/framework/views', 'storage/logs', 'bootstrap/cache'] as $dir) {
        if (! is_dir(LEX_ROOT.'/'.$dir)) {
            mkdir(LEX_ROOT.'/'.$dir, 0775, true);
        }
    }

    // Una configuración en caché impediría leer los cambios del .env.
    foreach (['config.php', 'routes-v7.php', 'events.php'] as $cache) {
        @unlink(LEX_ROOT.'/bootstrap/cache/'.$cache);
    }
}

/** Arranca Laravel en modo consola y devuelve el kernel de Artisan. */
function lex_boot(): Illuminate\Contracts\Console\Kernel
{
    require_once LEX_ROOT.'/vendor/autoload.php';
    $app = require LEX_ROOT.'/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    return $kernel;
}

function lex_installed(): bool
{
    try {
        return Illuminate\Support\Facades\Schema::hasTable('users')
            && Illuminate\Support\Facades\DB::table('users')->exists();
    } catch (Throwable) {
        return false;
    }
}

/** Verifica credenciales de un superadministrador (para restablecer desde el navegador). */
function lex_check_admin(string $email, string $password): bool
{
    $user = Illuminate\Support\Facades\DB::table('users')->where('email', $email)->where('role', 'superadmin')->first();

    return $user && Illuminate\Support\Facades\Hash::check($password, $user->password);
}

/** Ejecuta un comando Artisan y devuelve su salida. */
function lex_artisan(string $command, array $params = []): string
{
    Illuminate\Support\Facades\Artisan::call($command, $params);

    return trim(Illuminate\Support\Facades\Artisan::output());
}

// ---------------------------------------------------------------------------
// Modo consola
// ---------------------------------------------------------------------------
if ($cli) {
    $reset = in_array('--reset', $argv, true);
    echo "\n  LexGestión — instalación\n  ------------------------\n";

    foreach (lex_requirements() as $label => [$ok, $detail, $required]) {
        echo '  '.($ok ? '[OK]  ' : ($required ? '[FALTA]' : '[aviso]'))." {$label}".($ok ? '' : " — {$detail}")."\n";
    }

    if (! lex_requirements_ok()) {
        echo "\n  Corrija los requisitos marcados como [FALTA] y vuelva a ejecutar.\n\n";
        exit(1);
    }

    $log = [];
    lex_prepare($log);
    foreach ($log as $line) {
        echo "  · {$line}\n";
    }

    lex_boot();

    if ($reset) {
        echo "\n  Restableciendo datos de demostración (se borrará todo)…\n";
        echo lex_artisan('bufete:demo', ['--force' => true])."\n";
    } elseif (lex_installed()) {
        echo "\n  El sistema ya está instalado. Aplicando migraciones pendientes…\n";
        echo lex_artisan('migrate', ['--force' => true])."\n";
        echo "\n  Para borrar todo y cargar los datos de demostración: php setup.php --reset\n";
    } else {
        echo "\n  Instalando base de datos y datos de demostración…\n";
        echo lex_artisan('migrate', ['--force' => true, '--seed' => true])."\n";
    }

    echo "\n  Listo. Inicie con: php artisan serve  →  http://127.0.0.1:8000\n";
    echo "  Usuario: admin@bufete.test  ·  Contraseña: password\n\n";
    exit(0);
}

// ---------------------------------------------------------------------------
// Modo navegador
// ---------------------------------------------------------------------------
header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: DENY');
header('Cache-Control: no-store');

$remote = $_SERVER['REMOTE_ADDR'] ?? '';
$isLocal = in_array($remote, ['127.0.0.1', '::1'], true);
$webEnabled = lex_bool(lex_env('SETUP_WEB_ENABLED'), true);
$allowRemote = lex_bool(lex_env('SETUP_ALLOW_REMOTE'), false);

$blocked = null;
if (! $webEnabled) {
    $blocked = 'El instalador web está desactivado (SETUP_WEB_ENABLED=false). Use la consola: php setup.php --reset';
} elseif (! $isLocal && ! $allowRemote) {
    $blocked = 'Por seguridad, setup.php sólo puede usarse desde el mismo equipo (localhost). Use la consola: php setup.php --reset';
}

$log = [];
$error = null;
$done = false;
$installed = false;
$requirementsOk = lex_requirements_ok();

if (! $blocked && $requirementsOk) {
    try {
        lex_prepare($log);
        lex_boot();
        $installed = lex_installed();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            if (($_POST['confirm'] ?? '') !== 'RESTABLECER') {
                $error = 'Escriba RESTABLECER (en mayúsculas) para confirmar.';
            } elseif ($installed && ! lex_check_admin($_POST['email'] ?? '', $_POST['password'] ?? '')) {
                $error = 'Credenciales de superadministrador incorrectas.';
            } else {
                $log[] = lex_artisan('bufete:demo', ['--force' => true]);
                $done = true;
                $installed = true;
            }
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$loginUrl = $base.'/login';
$h = fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>LexGestión · Instalación</title>
    <style>
        :root { --p: #4f46e5; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background: #f3f4f6; color: #111827; }
        .wrap { max-width: 760px; margin: 40px auto; padding: 0 16px; }
        .card { background: #fff; border-radius: 14px; box-shadow: 0 1px 3px rgba(0,0,0,.08); padding: 28px; margin-bottom: 20px; }
        h1 { margin: 0 0 4px; font-size: 1.6rem; } h2 { font-size: 1.1rem; margin: 0 0 14px; }
        .muted { color: #6b7280; font-size: .9rem; }
        table { width: 100%; border-collapse: collapse; font-size: .9rem; }
        td { padding: 7px 4px; border-bottom: 1px solid #f1f1f4; }
        .ok { color: #059669; font-weight: 600; } .bad { color: #dc2626; font-weight: 600; } .warn { color: #d97706; font-weight: 600; }
        label { display: block; font-size: .85rem; font-weight: 600; margin: 12px 0 4px; }
        input { width: 100%; padding: 9px 11px; border: 1px solid #d1d5db; border-radius: 8px; font-size: .95rem; }
        button, .btn { display: inline-block; margin-top: 16px; background: var(--p); color: #fff; border: 0; padding: 10px 18px; border-radius: 8px; font-weight: 600; cursor: pointer; text-decoration: none; font-size: .95rem; }
        .danger { background: #dc2626; }
        .alert { padding: 12px 14px; border-radius: 8px; margin-bottom: 16px; font-size: .92rem; }
        .alert-error { background: #fef2f2; color: #991b1b; } .alert-ok { background: #ecfdf5; color: #065f46; } .alert-warn { background: #fffbeb; color: #92400e; }
        pre { background: #111827; color: #e5e7eb; padding: 12px; border-radius: 8px; overflow: auto; font-size: .8rem; white-space: pre-wrap; }
        code { background: #f3f4f6; padding: 1px 6px; border-radius: 4px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>⚖️ LexGestión</h1>
        <p class="muted">Sistema de gestión de casos legales · Instalación y datos de demostración</p>
    </div>

    <?php if ($blocked): ?>
        <div class="card"><div class="alert alert-error"><?= $h($blocked) ?></div></div>
    <?php else: ?>
        <div class="card">
            <h2>1. Requisitos del servidor</h2>
            <table>
                <?php foreach (lex_requirements() as $label => [$ok, $detail, $required]): ?>
                    <tr>
                        <td><?= $h($label) ?></td>
                        <td class="muted"><?= $h($detail) ?></td>
                        <td style="text-align:right" class="<?= $ok ? 'ok' : ($required ? 'bad' : 'warn') ?>"><?= $ok ? '✔ OK' : ($required ? '✘ Falta' : '! Opcional') ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <?php if (! $requirementsOk): ?>
            <div class="card"><div class="alert alert-error">Corrija los requisitos marcados antes de continuar. En XAMPP active las extensiones en <code>php.ini</code> y reinicie Apache.</div></div>
        <?php else: ?>
            <div class="card">
                <h2>2. <?= $installed ? 'Restablecer datos de demostración' : 'Instalar' ?></h2>

                <?php if ($error): ?><div class="alert alert-error"><?= $h($error) ?></div><?php endif; ?>

                <?php if ($done): ?>
                    <div class="alert alert-ok"><strong>¡Listo!</strong> Se cargaron los datos de demostración.<br>
                        Usuario: <code>admin@bufete.test</code> · Contraseña: <code>password</code></div>
                    <a class="btn" href="<?= $h($loginUrl) ?>">Ir al inicio de sesión →</a>
                <?php else: ?>
                    <?php if ($installed): ?>
                        <div class="alert alert-warn">⚠ El sistema ya tiene datos. Al continuar se <strong>borrarán definitivamente</strong> todos los clientes, casos, documentos, pagos y usuarios, y se cargarán los datos de ejemplo. Cree antes una copia de seguridad desde <em>Configuración</em>.</div>
                    <?php else: ?>
                        <p class="muted">Se crearán las tablas y se cargarán datos de ejemplo (abogados, clientes, casos, audiencias, pagos…).</p>
                    <?php endif; ?>

                    <form method="post" autocomplete="off">
                        <?php if ($installed): ?>
                            <label>Correo de un superadministrador</label>
                            <input type="email" name="email" required value="<?= $h($_POST['email'] ?? '') ?>">
                            <label>Contraseña</label>
                            <input type="password" name="password" required>
                        <?php endif; ?>
                        <label>Escriba <code>RESTABLECER</code> para confirmar</label>
                        <input name="confirm" required pattern="RESTABLECER" placeholder="RESTABLECER">
                        <button type="submit" class="<?= $installed ? 'danger' : '' ?>"><?= $installed ? 'Borrar todo y cargar datos de demostración' : 'Instalar con datos de demostración' ?></button>
                    </form>
                    <?php if ($installed): ?><p class="muted" style="margin-top:16px"><a href="<?= $h($loginUrl) ?>">← Volver al sistema sin cambios</a></p><?php endif; ?>
                <?php endif; ?>

                <?php if ($log): ?><pre><?= $h(implode("\n", $log)) ?></pre><?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <p class="muted" style="text-align:center">Consola: <code>php setup.php</code> · <code>php setup.php --reset</code> · <code>php artisan bufete:demo</code></p>
</div>
</body>
</html>
