<?php

namespace Tests\Feature\Bufete;

use App\Enums\ExpenseStatus;
use App\Models\Appointment;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Support\NumberToWords;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FinanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_payments_get_consecutive_receipts_and_a_printable_receipt(): void
    {
        $senior = User::factory()->senior()->create();
        $case = $this->createCase($senior);

        foreach ([1500000, 250000] as $amount) {
            $this->actingAs($senior)->post('/payments', [
                'client_id' => $case->client_id, 'legal_case_id' => $case->id, 'amount' => $amount,
                'method' => 'transferencia', 'paid_at' => today()->format('Y-m-d'), 'concept' => 'Abono honorarios',
            ])->assertRedirect();
        }

        $year = now()->year;
        $this->assertSame(["REC-{$year}-00001", "REC-{$year}-00002"], Payment::orderBy('id')->pluck('receipt_number')->all());

        $first = Payment::orderBy('id')->first();
        $this->actingAs($senior)->get("/payments/{$first->id}")
            ->assertOk()
            ->assertSee("REC-{$year}-00001")
            ->assertSee('UN MILLÓN QUINIENTOS MIL PESOS M/CTE');

        $this->assertSame(0.0, $case->fresh()->balance());
    }

    public function test_payment_case_must_belong_to_the_client(): void
    {
        $admin = User::factory()->superadmin()->create();
        $case = $this->createCase($admin);
        $otherClient = $this->createClient($admin);

        $this->actingAs($admin)->post('/payments', [
            'client_id' => $otherClient->id, 'legal_case_id' => $case->id, 'amount' => 100,
            'method' => 'efectivo', 'paid_at' => today()->format('Y-m-d'), 'concept' => 'X',
        ])->assertSessionHasErrors('legal_case_id');
    }

    public function test_junior_lawyers_can_register_but_not_edit_payments(): void
    {
        $junior = User::factory()->junior()->create();
        $case = $this->createCase($junior);

        $this->actingAs($junior)->post('/payments', [
            'client_id' => $case->client_id, 'legal_case_id' => $case->id, 'amount' => 1000,
            'method' => 'efectivo', 'paid_at' => today()->format('Y-m-d'), 'concept' => 'Abono',
        ])->assertRedirect();

        $payment = Payment::first();
        $this->actingAs($junior)->get("/payments/{$payment->id}")->assertOk();
        $this->actingAs($junior)->get("/payments/{$payment->id}/edit")->assertForbidden();
        $this->actingAs($junior)->delete("/payments/{$payment->id}")->assertForbidden();
    }

    public function test_expense_approval_and_reimbursement_workflow(): void
    {
        Storage::fake('local');
        Notification::fake();

        $admin = User::factory()->superadmin()->create();
        $senior = User::factory()->senior()->create();
        $junior = User::factory()->junior()->create();
        $case = $this->createCase($senior, $junior);

        // 1. El junior registra el gasto con soporte.
        $this->actingAs($junior)->post('/expenses', [
            'legal_case_id' => $case->id, 'category' => 'copias', 'description' => 'Copias del expediente',
            'amount' => 45000, 'expense_date' => today()->format('Y-m-d'), 'billable' => 1,
            'receipt' => UploadedFile::fake()->create('factura.pdf', 50, 'application/pdf'),
        ])->assertRedirect();

        $expense = Expense::first();
        $this->assertSame(ExpenseStatus::Pending, $expense->status);
        Storage::disk('local')->assertExists($expense->receipt_path);
        Notification::assertSentTo([$admin, $senior], AppNotification::class);

        // 2. El junior no puede aprobar su propio gasto; el senior responsable sí.
        $this->actingAs($junior)->post("/expenses/{$expense->id}/approve")->assertForbidden();
        $this->actingAs($senior)->post("/expenses/{$expense->id}/approve")->assertRedirect();
        $this->assertSame(ExpenseStatus::Approved, $expense->fresh()->status);
        Notification::assertSentTo($junior, AppNotification::class);

        // 3. Sólo el superadministrador marca el reembolso.
        $this->actingAs($senior)->post("/expenses/{$expense->id}/reimburse")->assertForbidden();
        $this->actingAs($admin)->post("/expenses/{$expense->id}/reimburse")->assertRedirect();
        $this->assertSame(ExpenseStatus::Reimbursed, $expense->fresh()->status);
        $this->assertSame($admin->id, $expense->fresh()->reimbursed_by);
    }

    public function test_rejecting_an_expense_requires_a_reason(): void
    {
        $senior = User::factory()->senior()->create();
        $junior = User::factory()->junior()->create();
        $case = $this->createCase($senior, $junior);
        $expense = Expense::create([
            'legal_case_id' => $case->id, 'user_id' => $junior->id, 'category' => 'otro', 'description' => 'Taxi',
            'amount' => 20000, 'expense_date' => today(), 'status' => 'pendiente',
        ]);

        $this->actingAs($senior)->post("/expenses/{$expense->id}/reject")->assertSessionHasErrors('review_notes');
        $this->actingAs($senior)->post("/expenses/{$expense->id}/reject", ['review_notes' => 'Sin soporte'])->assertRedirect();
        $this->assertSame(ExpenseStatus::Rejected, $expense->fresh()->status);
    }

    public function test_appointments_cannot_overlap_for_the_same_lawyer(): void
    {
        $senior = User::factory()->senior()->create();
        $client = $this->createClient($senior);
        $start = now()->addDay()->setTime(10, 0);

        Appointment::create([
            'client_id' => $client->id, 'user_id' => $senior->id, 'title' => 'Consulta',
            'starts_at' => $start, 'duration_minutes' => 60, 'mode' => 'presencial', 'status' => 'confirmada',
        ]);

        $payload = [
            'client_id' => $client->id, 'user_id' => $senior->id, 'title' => 'Otra', 'duration_minutes' => 30,
            'mode' => 'presencial', 'status' => 'pendiente', 'fee' => 0,
        ];

        $this->actingAs($senior)->post('/appointments', $payload + ['starts_at' => $start->copy()->addMinutes(30)->format('Y-m-d H:i')])
            ->assertSessionHasErrors('starts_at');

        $this->actingAs($senior)->post('/appointments', $payload + ['starts_at' => $start->copy()->addMinutes(60)->format('Y-m-d H:i')])
            ->assertRedirect('/appointments');
    }

    public function test_prospect_appointments_do_not_need_a_client(): void
    {
        $senior = User::factory()->senior()->create();

        $this->actingAs($senior)->post('/appointments', [
            'contact_name' => 'Persona interesada', 'contact_phone' => '3000000000', 'user_id' => $senior->id,
            'title' => 'Consulta inicial', 'starts_at' => now()->addDay()->format('Y-m-d H:i'), 'duration_minutes' => 30,
            'mode' => 'telefonica', 'status' => 'pendiente', 'fee' => 80000,
        ])->assertRedirect('/appointments');

        $this->assertSame('Persona interesada', Appointment::first()->who);
    }

    public function test_number_to_words_in_spanish(): void
    {
        $this->assertSame('CIEN PESOS M/CTE', NumberToWords::convert(100));
        $this->assertSame('VEINTIÚN MIL QUINIENTOS PESOS M/CTE', NumberToWords::convert(21500));
        $this->assertSame('UN MILLÓN DE PESOS M/CTE', NumberToWords::convert(1000000));
        $this->assertSame('DOS MILLONES TRESCIENTOS CUARENTA Y CINCO MIL SEISCIENTOS SETENTA Y OCHO PESOS M/CTE', NumberToWords::convert(2345678));
        $this->assertSame('CIENTO VEINTE DÓLARES CON 50/100', NumberToWords::convert(120.5, 'DÓLARES', ''));
    }

    public function test_lawyers_cannot_register_payments_or_appointments_for_foreign_records(): void
    {
        $junior = User::factory()->junior()->create();
        $foreignCase = $this->createCase(User::factory()->senior()->create());

        $this->actingAs($junior)->post('/payments', [
            'client_id' => $foreignCase->client_id, 'legal_case_id' => $foreignCase->id, 'amount' => 100,
            'method' => 'efectivo', 'paid_at' => today()->format('Y-m-d'), 'concept' => 'X',
        ])->assertSessionHasErrors(['client_id', 'legal_case_id']);

        $this->actingAs($junior)->post('/appointments', [
            'client_id' => $foreignCase->client_id, 'user_id' => $junior->id, 'title' => 'Cita',
            'starts_at' => now()->addDay()->format('Y-m-d H:i'), 'duration_minutes' => 30,
            'mode' => 'presencial', 'status' => 'pendiente',
        ])->assertSessionHasErrors('client_id');

        $this->assertSame(0, Payment::count() + Appointment::count());
    }
}
