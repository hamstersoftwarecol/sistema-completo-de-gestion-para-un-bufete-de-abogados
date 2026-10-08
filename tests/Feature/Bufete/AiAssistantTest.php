<?php

namespace Tests\Feature\Bufete;

use App\Models\Hearing;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiAssistantTest extends TestCase
{
    use RefreshDatabase;

    public function test_offline_mode_answers_natural_language_questions_with_firm_data(): void
    {
        Http::fake();
        $senior = User::factory()->senior()->create();
        $case = $this->createCase($senior);
        Hearing::create([
            'legal_case_id' => $case->id, 'user_id' => $senior->id, 'title' => 'Audiencia de conciliación',
            'scheduled_at' => now()->addDays(2), 'status' => 'programada',
        ]);

        $this->actingAs($senior)->postJson('/ai/message', ['message' => '¿Qué audiencias tengo esta semana?'])
            ->assertOk()
            ->assertJsonPath('reply.provider', 'local')
            ->assertJsonPath('reply.role', 'assistant')
            ->assertJsonFragment(['provider' => 'local']);

        $this->assertStringContainsString('Audiencia de conciliación', $senior->aiConversations()->first()->messages->last()->content);

        Http::assertNothingSent();
        $this->assertSame(1, $senior->aiConversations()->count());
    }

    public function test_gemini_is_called_with_system_instructions_context_and_history(): void
    {
        Setting::putMany(['ai_provider' => 'gemini', 'gemini_api_key' => 'test-key', 'gemini_model' => 'gemini-2.5-flash']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'Tiene **1** caso activo.']]]]],
            ]),
        ]);

        $user = User::factory()->senior()->create(['ai_instructions' => 'Responde siempre con viñetas.']);
        $case = $this->createCase($user);

        $response = $this->actingAs($user)->postJson('/ai/message', ['message' => 'Resume mis casos'])
            ->assertOk()
            ->assertJsonPath('reply.provider', 'gemini');

        $this->assertStringContainsString('<strong>1</strong>', $response->json('reply.html'));

        Http::assertSent(function (Request $request) use ($case) {
            $system = $request['system_instruction']['parts'][0]['text'];

            return str_contains($request->url(), 'models/gemini-2.5-flash:generateContent')
                && $request->hasHeader('x-goog-api-key', 'test-key')
                && str_contains($system, 'Responde siempre con viñetas.')
                && str_contains($system, $case->case_number)
                && $request['contents'][0]['parts'][0]['text'] === 'Resume mis casos';
        });

        // La clave se guarda cifrada en la base de datos.
        $this->assertNotSame('test-key', Setting::where('key', 'gemini_api_key')->value('value'));
    }

    public function test_openai_provider_and_error_handling(): void
    {
        Setting::putMany(['ai_provider' => 'openai', 'openai_api_key' => 'sk-test', 'openai_model' => 'gpt-4o-mini']);
        Http::fakeSequence('api.openai.com/*')
            ->push(['choices' => [['message' => ['content' => 'Hola, ¿en qué le ayudo?']]]])
            ->push(['error' => ['message' => 'Invalid API key']], 401);

        $user = User::factory()->junior()->create();

        $first = $this->actingAs($user)->postJson('/ai/message', ['message' => 'Hola'])
            ->assertOk()->assertJsonPath('reply.provider', 'openai');

        $this->actingAs($user)->postJson('/ai/message', ['message' => 'Otra', 'conversation_id' => $first->json('conversation.id')])
            ->assertStatus(422)
            ->assertJsonPath('error', 'OpenAI respondió con error: Invalid API key');
    }

    public function test_ai_conversations_are_private(): void
    {
        $owner = User::factory()->junior()->create();
        $admin = User::factory()->superadmin()->create();
        $conversation = $owner->aiConversations()->create(['title' => 'Privada']);

        $this->actingAs($admin)->get("/ai/{$conversation->id}")->assertNotFound();
        $this->actingAs($owner)->get("/ai/{$conversation->id}")->assertOk()->assertSee('Privada');
    }

    public function test_users_can_save_custom_instructions(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/ai/instructions', ['ai_instructions' => 'Sé breve.'])->assertRedirect();

        $this->assertSame('Sé breve.', $user->fresh()->ai_instructions);
    }

    public function test_the_latest_question_is_answered_and_history_is_sent_in_order(): void
    {
        Setting::putMany(['ai_provider' => 'gemini', 'gemini_api_key' => 'k']);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push(['candidates' => [['content' => ['parts' => [['text' => 'Respuesta 1']]]]]])
            ->push(['candidates' => [['content' => ['parts' => [['text' => 'Respuesta 2']]]]]]),
        ]);
        $user = User::factory()->senior()->create();

        $first = $this->actingAs($user)->postJson('/ai/message', ['message' => 'Primera pregunta']);
        $this->actingAs($user)->postJson('/ai/message', ['message' => 'Segunda pregunta', 'conversation_id' => $first->json('conversation.id')])
            ->assertOk()->assertJsonPath('reply.html', "<p>Respuesta 2</p>\n");

        Http::assertSent(fn (Request $r) => array_column(array_column($r['contents'], 'parts'), 0) === [
            ['text' => 'Primera pregunta'], ['text' => 'Respuesta 1'], ['text' => 'Segunda pregunta'],
        ]);
    }

    public function test_offline_mode_answers_the_latest_question(): void
    {
        $user = User::factory()->senior()->create();

        $first = $this->actingAs($user)->postJson('/ai/message', ['message' => '¿Qué audiencias tengo?']);
        $second = $this->actingAs($user)->postJson('/ai/message', ['message' => '¿Cuánto me deben los clientes?', 'conversation_id' => $first->json('conversation.id')]);

        $this->assertStringContainsString('Resumen financiero', $second->json('reply.html'));
    }
}
