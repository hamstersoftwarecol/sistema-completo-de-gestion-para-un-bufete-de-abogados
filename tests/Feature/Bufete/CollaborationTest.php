<?php

namespace Tests\Feature\Bufete;

use App\Mail\ClientMessage;
use App\Models\Message;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CollaborationTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_chat_between_lawyers(): void
    {
        $laura = User::factory()->senior()->create();
        $daniela = User::factory()->junior()->create();

        $this->actingAs($laura)->postJson("/chat/{$daniela->id}/messages", ['body' => '¿Radicaste el memorial?'])
            ->assertCreated()
            ->assertJsonPath('message.mine', true);

        $this->actingAs($daniela)->getJson('/chat/unread')->assertJsonPath('total', 1);

        $this->actingAs($daniela)->getJson("/chat/{$laura->id}/messages")
            ->assertOk()
            ->assertJsonPath('messages.0.body', '¿Radicaste el memorial?')
            ->assertJsonPath('messages.0.mine', false);

        $this->assertNotNull(Message::first()->read_at);
        $this->actingAs($daniela)->getJson('/chat/unread')->assertJsonPath('total', 0);
    }

    public function test_emails_and_calls_are_logged_in_the_client_history(): void
    {
        Mail::fake();
        $senior = User::factory()->senior()->create();
        $client = $this->createClient($senior, ['email' => 'cliente@example.com']);

        $this->actingAs($senior)->post("/clients/{$client->id}/email", [
            'subject' => 'Estado de su proceso', 'body' => 'Le informamos que…',
        ])->assertSessionHas('success');

        Mail::assertSent(ClientMessage::class, fn ($mail) => $mail->hasTo('cliente@example.com'));

        $this->actingAs($senior)->post("/clients/{$client->id}/communications", [
            'type' => 'llamada', 'subject' => 'Seguimiento', 'body' => 'Confirmó asistencia',
        ])->assertSessionHas('success');

        $this->assertSame(['email', 'llamada'], $client->communications()->orderBy('id')->get()->map(fn ($c) => $c->type->value)->all());
        $this->actingAs($senior)->get("/clients/{$client->id}")->assertOk()->assertSee('Estado de su proceso');
    }

    public function test_superadmin_can_log_in_as_another_lawyer_and_come_back(): void
    {
        $admin = User::factory()->superadmin()->create();
        $junior = User::factory()->junior()->create();

        $this->actingAs($admin)->post("/admin/users/{$junior->id}/impersonate")->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($junior);

        $this->get('/dashboard')->assertSee('Está viendo el sistema como');
        $this->get('/admin/users')->assertForbidden();

        $this->post('/impersonate/leave')->assertRedirect('/admin/users');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_non_admins_cannot_impersonate(): void
    {
        $senior = User::factory()->senior()->create();
        $junior = User::factory()->junior()->create();

        $this->actingAs($senior)->post("/admin/users/{$junior->id}/impersonate")->assertForbidden();
        $this->actingAs($senior)->post('/impersonate/leave')->assertForbidden();
    }

    public function test_notifications_open_internal_links_and_mark_as_read(): void
    {
        $senior = User::factory()->senior()->create();
        $case = $this->createCase($senior);
        $senior->notify(new AppNotification('Caso asignado', 'Mensaje', route('cases.show', $case, false)));
        $senior->notify(new AppNotification('Externo', 'x', 'https://malicioso.example.com'));

        [$internal, $external] = $senior->notifications()->orderBy('created_at')->get()->sortBy(fn ($n) => $n->data['title'])->values()->all();

        $this->actingAs($senior)->get("/notifications/{$internal->id}/open")->assertRedirect(url("/cases/{$case->id}"));
        $this->actingAs($senior)->get("/notifications/{$external->id}/open")->assertRedirect('/notifications');
        $this->assertSame(0, $senior->fresh()->unreadNotifications()->count());
    }
}
