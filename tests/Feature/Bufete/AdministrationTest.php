<?php

namespace Tests\Feature\Bufete;

use App\Models\CaseStatus;
use App\Models\CaseType;
use App\Models\Setting;
use App\Models\User;
use App\Services\BackupService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_are_managed_with_roles_and_protection(): void
    {
        $admin = User::factory()->superadmin()->create();

        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Nuevo Abogado', 'email' => 'nuevo@bufete.test', 'role' => 'senior',
            'password' => 'Secreta123', 'password_confirmation' => 'Secreta123', 'is_active' => 1,
        ])->assertRedirect('/admin/users');

        $user = User::firstWhere('email', 'nuevo@bufete.test');
        $this->assertTrue($user->isSenior());

        // No se elimina un usuario con casos asignados.
        $this->createCase($user);
        $this->actingAs($admin)->delete("/admin/users/{$user->id}")->assertSessionHas('error');
        $this->assertModelExists($user);

        // Se puede desactivar.
        $this->actingAs($admin)->patch("/admin/users/{$user->id}/toggle")->assertSessionHas('success');
        $this->assertFalse($user->fresh()->is_active);

        // El administrador no puede quitarse su propio rol.
        $this->actingAs($admin)->put("/admin/users/{$admin->id}", [
            'name' => $admin->name, 'email' => $admin->email, 'role' => 'junior', 'is_active' => 1,
        ])->assertSessionHas('error');
        $this->assertTrue($admin->fresh()->isSuperadmin());
    }

    public function test_master_data_in_use_cannot_be_deleted(): void
    {
        $admin = User::factory()->superadmin()->create();
        $case = $this->createCase($admin);

        $this->actingAs($admin)->delete("/admin/case-types/{$case->case_type_id}")->assertSessionHas('error');
        $this->actingAs($admin)->delete("/admin/case-statuses/{$case->case_status_id}")->assertSessionHas('error');

        $this->actingAs($admin)->post('/admin/case-types', ['name' => 'Agrario', 'is_active' => 1])->assertRedirect();
        $type = CaseType::firstWhere('name', 'Agrario');
        $this->actingAs($admin)->delete("/admin/case-types/{$type->id}")->assertSessionHas('success');

        $this->actingAs($admin)->post('/admin/case-statuses', ['name' => 'Cerrado', 'color' => 'gray', 'is_closed' => 1])->assertRedirect();
        $this->assertTrue(CaseStatus::firstWhere('name', 'Cerrado')->is_closed);
    }

    public function test_theme_logo_and_ai_keys_can_be_configured(): void
    {
        Storage::fake('local');
        $admin = User::factory()->superadmin()->create();

        $this->actingAs($admin)->post('/admin/settings/appearance', [
            'theme_color' => 'emerald',
            'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
        ])->assertRedirect();

        Setting::flush();
        $this->assertSame('emerald', setting('theme_color'));
        $this->get('/branding/logo')->assertOk();
        $this->actingAs($admin)->get('/dashboard')->assertSee('--primary-500: 16 185 129', false);

        $this->actingAs($admin)->put('/admin/settings/ai', [
            'ai_provider' => 'openai', 'openai_api_key' => 'sk-secret', 'openai_model' => 'gpt-4o-mini',
            'ai_include_context' => 1,
        ])->assertRedirect();

        Setting::flush();
        $this->assertSame('sk-secret', setting('openai_api_key'));

        // Guardar sin escribir una clave nueva conserva la existente.
        $this->actingAs($admin)->put('/admin/settings/ai', ['ai_provider' => 'openai'])->assertRedirect();
        Setting::flush();
        $this->assertSame('sk-secret', setting('openai_api_key'));
    }

    public function test_backups_can_be_created_downloaded_and_deleted(): void
    {
        $admin = User::factory()->superadmin()->create();

        // La base en memoria de las pruebas no se puede copiar a disco: se simula el volcado.
        $service = new class extends BackupService
        {
            public function directory(): string
            {
                $dir = storage_path('framework/testing/backups');
                File::ensureDirectoryExists($dir);

                return $dir;
            }

            protected function copyDatabase(string $target): void
            {
                file_put_contents($target, 'SQLite format 3');
            }
        };
        $this->app->instance(BackupService::class, $service);
        File::cleanDirectory($service->directory());

        $this->actingAs($admin)->post('/admin/backups')->assertSessionHas('success');

        $backup = $service->list()[0]['name'];
        $this->actingAs($admin)->get("/admin/backups/{$backup}")->assertOk();
        $this->actingAs($admin)->get('/admin/backups/..%2F.env')->assertNotFound();

        $this->actingAs($admin)->delete("/admin/backups/{$backup}")->assertSessionHas('success');
        $this->assertSame([], $service->list());
    }

    public function test_demo_data_seeder_builds_a_consistent_firm(): void
    {
        Storage::fake('local');
        $this->seed(DatabaseSeeder::class);

        $admin = User::firstWhere('email', 'admin@bufete.test');
        $this->assertTrue($admin->isSuperadmin());
        $this->assertSame(5, User::count());

        $this->actingAs($admin)->get('/dashboard')->assertOk()->assertSee('Mejía &amp; Asociados', false);

        $junior = User::firstWhere('email', 'junior@bufete.test');
        $this->actingAs($junior)->get('/cases?scope=all')->assertOk();
    }
}
