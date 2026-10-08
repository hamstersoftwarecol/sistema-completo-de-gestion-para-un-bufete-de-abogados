<?php

namespace Tests\Feature\Bufete;

use App\Models\CaseStatus;
use App\Models\Court;
use App\Models\Hearing;
use App\Models\LegalCase;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CaseManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_case_can_be_created_with_court_judge_type_and_documents(): void
    {
        Storage::fake('local');
        Notification::fake();

        $senior = User::factory()->senior()->create();
        $junior = User::factory()->junior()->create();
        $client = $this->createClient($senior);
        $status = CaseStatus::create(['name' => 'Abierto', 'color' => 'blue']);
        $court = Court::create(['name' => 'Juzgado 1 Civil', 'city' => 'Bogotá']);

        $response = $this->actingAs($senior)->post('/cases', [
            'case_number' => '11001-31-03-001-2026-00001-00',
            'title' => 'Proceso ejecutivo',
            'client_id' => $client->id,
            'case_status_id' => $status->id,
            'court_id' => $court->id,
            'judge' => 'Dra. Ana Pérez',
            'lawyer_id' => $senior->id,
            'assistant_id' => $junior->id,
            'priority' => 'alta',
            'fee_amount' => 5000000,
            'documents' => [UploadedFile::fake()->create('demanda.pdf', 120, 'application/pdf')],
        ]);

        $case = LegalCase::firstWhere('case_number', '11001-31-03-001-2026-00001-00');
        $response->assertRedirect("/cases/{$case->id}");
        $this->assertSame('Dra. Ana Pérez', $case->judge);
        $this->assertCount(1, $case->documents);
        Storage::disk('local')->assertExists($case->documents->first()->path);
        Notification::assertSentTo($junior, AppNotification::class);
        Notification::assertNotSentTo($senior, AppNotification::class);
    }

    public function test_duplicate_case_numbers_are_rejected_and_detected_live(): void
    {
        $senior = User::factory()->senior()->create();
        $existing = $this->createCase($senior, attributes: ['case_number' => 'RAD-2026-0001']);

        $this->actingAs($senior)->getJson('/cases/check-number?number=rad-2026-0001')
            ->assertOk()->assertJson(['exists' => true]);

        $this->actingAs($senior)->getJson("/cases/check-number?number=RAD-2026-0001&ignore={$existing->id}")
            ->assertJson(['exists' => false]);

        $this->actingAs($senior)->post('/cases', [
            'case_number' => 'RAD-2026-0001',
            'title' => 'Otro',
            'client_id' => $existing->client_id,
            'case_status_id' => $existing->case_status_id,
            'lawyer_id' => $senior->id,
            'priority' => 'media',
        ])->assertSessionHasErrors('case_number');
    }

    public function test_a_lawyer_must_remain_assigned_to_the_cases_they_create(): void
    {
        $junior = User::factory()->junior()->create();
        $other = User::factory()->senior()->create();
        $client = $this->createClient($junior);
        $status = CaseStatus::create(['name' => 'Abierto', 'color' => 'blue']);

        $this->actingAs($junior)->post('/cases', [
            'case_number' => 'X-1', 'title' => 'Caso', 'client_id' => $client->id,
            'case_status_id' => $status->id, 'lawyer_id' => $other->id, 'priority' => 'media',
        ])->assertSessionHasErrors('lawyer_id');
    }

    public function test_cases_with_linked_records_cannot_be_deleted(): void
    {
        $admin = User::factory()->superadmin()->create();
        $case = $this->createCase($admin);

        Payment::create([
            'receipt_number' => 'REC-1', 'client_id' => $case->client_id, 'legal_case_id' => $case->id,
            'amount' => 100, 'method' => 'efectivo', 'paid_at' => today(), 'concept' => 'Abono',
        ]);

        $this->actingAs($admin)->delete("/cases/{$case->id}")->assertSessionHas('error');
        $this->assertModelExists($case);

        // Un caso sin registros vinculados sí se elimina (sus notas y partes se borran con él).
        $empty = $this->createCase($admin);
        $empty->notes()->create(['body' => 'Nota', 'user_id' => $admin->id]);
        $this->actingAs($admin)->delete("/cases/{$empty->id}")->assertRedirect('/cases');
        $this->assertModelMissing($empty);
    }

    public function test_clients_with_cases_cannot_be_deleted(): void
    {
        $admin = User::factory()->superadmin()->create();
        $case = $this->createCase($admin);

        $this->actingAs($admin)->delete("/clients/{$case->client_id}")->assertSessionHas('error');
        $this->assertModelExists($case->client);
    }

    public function test_junior_lawyers_cannot_delete_cases(): void
    {
        $junior = User::factory()->junior()->create();
        $case = $this->createCase($junior);

        $this->actingAs($junior)->delete("/cases/{$case->id}")->assertForbidden();
    }

    public function test_parties_and_notes_can_be_managed(): void
    {
        $senior = User::factory()->senior()->create();
        $case = $this->createCase($senior);

        $this->actingAs($senior)->post("/cases/{$case->id}/parties", ['name' => 'Juan Demandado', 'role' => 'demandado'])->assertRedirect();
        $this->actingAs($senior)->post("/cases/{$case->id}/notes", ['body' => 'Se radicó memorial', 'is_pinned' => 1])->assertRedirect();

        $this->assertSame('Juan Demandado', $case->parties()->first()->name);
        $this->assertTrue($case->notes()->first()->is_pinned);

        $party = $case->parties()->first();
        $this->actingAs($senior)->put("/parties/{$party->id}", ['name' => 'Juan D. Pérez', 'role' => 'demandado'])->assertRedirect();
        $this->assertSame('Juan D. Pérez', $party->fresh()->name);
    }

    public function test_documents_are_private_to_the_case_team(): void
    {
        Storage::fake('local');
        $senior = User::factory()->senior()->create();
        $outsider = User::factory()->junior()->create();
        $case = $this->createCase($senior);

        $this->actingAs($senior)->post("/cases/{$case->id}/documents", [
            'documents' => [UploadedFile::fake()->image('foto.jpg')],
            'category' => 'prueba',
        ])->assertRedirect();

        $doc = $case->documents()->first();
        $this->actingAs($senior)->get("/documents/{$doc->id}")->assertOk();
        $this->actingAs($senior)->get("/documents/{$doc->id}/download")->assertOk();
        $this->actingAs($outsider)->get("/documents/{$doc->id}")->assertForbidden();
    }

    public function test_hearings_can_be_scheduled_and_appear_in_the_calendar(): void
    {
        Notification::fake();
        $senior = User::factory()->senior()->create();
        $junior = User::factory()->junior()->create();
        $case = $this->createCase($senior, $junior);

        $this->actingAs($senior)->post('/hearings', [
            'legal_case_id' => $case->id,
            'title' => 'Audiencia inicial',
            'scheduled_at' => now()->addDays(3)->format('Y-m-d H:i'),
            'duration_minutes' => 60,
            'status' => 'programada',
            'user_id' => $senior->id,
        ])->assertRedirect('/hearings');

        Notification::assertSentTo($junior, AppNotification::class);

        $this->actingAs($junior)->getJson('/calendar/events?start='.now()->format('Y-m-d').'&end='.now()->addDays(10)->format('Y-m-d'))
            ->assertOk()
            ->assertJsonFragment(['kind' => 'Audiencia']);
    }

    public function test_hearing_reminders_are_sent_once(): void
    {
        Notification::fake();
        $senior = User::factory()->senior()->create();
        $case = $this->createCase($senior);
        $hearing = Hearing::create([
            'legal_case_id' => $case->id, 'user_id' => $senior->id, 'title' => 'Lectura de fallo',
            'scheduled_at' => now()->addHours(5), 'status' => 'programada',
        ]);

        $this->artisan('bufete:recordatorios')->assertSuccessful();
        $this->artisan('bufete:recordatorios')->assertSuccessful();

        Notification::assertSentToTimes($senior, AppNotification::class, 1);
        $this->assertNotNull($hearing->fresh()->reminder_sent_at);
    }
}
