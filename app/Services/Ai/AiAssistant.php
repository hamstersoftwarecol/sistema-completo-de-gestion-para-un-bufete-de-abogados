<?php

namespace App\Services\Ai;

use App\Models\AiConversation;
use App\Models\User;

/**
 * Orquesta el asistente: elige proveedor (Gemini / OpenAI / sin conexión),
 * arma las instrucciones del sistema y el historial de la conversación.
 */
class AiAssistant
{
    /**
     * @return array{content:string,provider:string}
     */
    public function reply(User $user, AiConversation $conversation): array
    {
        $history = $conversation->messages()
            ->reorder()
            ->latest('id')
            ->limit(config('bufete.ai.history_limit'))
            ->get()
            ->reverse()
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
            ->values()
            ->all();

        $client = $this->client();

        if (! $client) {
            $last = collect($history)->last(fn ($m) => $m['role'] === 'user');

            return ['content' => (new LocalAssistant)->reply($user, $last['content'] ?? ''), 'provider' => 'local'];
        }

        return ['content' => $client->chat($this->systemPrompt($user), $history), 'provider' => self::provider()];
    }

    public function systemPrompt(User $user): string
    {
        $parts = [setting('ai_system_prompt', config('bufete.ai.default_system_prompt'))];

        if (filled($user->ai_instructions)) {
            $parts[] = "## Instrucciones personalizadas del usuario\n".$user->ai_instructions;
        }

        if (setting('ai_include_context', '1') === '1') {
            $parts[] = "## Datos actuales del bufete (contexto)\n".ContextBuilder::build($user);
        }

        return implode("\n\n", $parts);
    }

    public static function provider(): string
    {
        $provider = setting('ai_provider', 'gemini');

        return match ($provider) {
            'gemini' => filled(setting('gemini_api_key')) ? 'gemini' : 'local',
            'openai' => filled(setting('openai_api_key')) ? 'openai' : 'local',
            default => 'local',
        };
    }

    public static function providerLabel(?string $provider = null): string
    {
        return match ($provider ?? self::provider()) {
            'gemini' => 'Google Gemini',
            'openai' => 'OpenAI ChatGPT',
            default => 'Modo sin conexión',
        };
    }

    public function client(?string $provider = null): GeminiClient|OpenAiClient|null
    {
        return match ($provider ?? self::provider()) {
            'gemini' => new GeminiClient(
                setting('gemini_api_key'),
                setting('gemini_model', config('bufete.ai.default_gemini_model')),
            ),
            'openai' => new OpenAiClient(
                setting('openai_api_key'),
                setting('openai_model', config('bufete.ai.default_openai_model')),
                setting('openai_base_url', config('bufete.ai.default_openai_base_url')),
            ),
            default => null,
        };
    }
}
