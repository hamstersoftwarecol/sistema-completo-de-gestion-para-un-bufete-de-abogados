<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;

class GeminiClient
{
    public function __construct(private string $apiKey, private string $model) {}

    /**
     * @param  array<int,array{role:string,content:string}>  $history
     */
    public function chat(string $system, array $history): string
    {
        $contents = [];
        foreach ($history as $msg) {
            $role = $msg['role'] === 'assistant' ? 'model' : 'user';
            $last = array_key_last($contents);

            // Gemini exige turnos alternos: se fusionan mensajes consecutivos del mismo rol.
            if ($last !== null && $contents[$last]['role'] === $role) {
                $contents[$last]['parts'][0]['text'] .= "\n\n".$msg['content'];

                continue;
            }

            $contents[] = ['role' => $role, 'parts' => [['text' => $msg['content']]]];
        }

        $response = Http::timeout(config('bufete.ai.timeout'))
            ->withHeaders(['x-goog-api-key' => $this->apiKey])
            ->acceptJson()
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent", [
                'system_instruction' => ['parts' => [['text' => $system]]],
                'contents' => $contents,
                'generationConfig' => ['temperature' => 0.4],
            ]);

        if ($response->failed()) {
            throw new AiException('Gemini respondió con error: '.($response->json('error.message') ?? $response->status()));
        }

        $text = collect($response->json('candidates.0.content.parts', []))->pluck('text')->filter()->implode('');

        if ($text === '') {
            $reason = $response->json('candidates.0.finishReason') ?? $response->json('promptFeedback.blockReason') ?? 'desconocido';
            throw new AiException("Gemini no devolvió texto (motivo: {$reason}).");
        }

        return $text;
    }
}
