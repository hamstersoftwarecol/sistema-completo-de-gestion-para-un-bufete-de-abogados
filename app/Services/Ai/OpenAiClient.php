<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;

/**
 * Cliente para la API de OpenAI (ChatGPT) o cualquier servicio compatible
 * con /chat/completions (por ejemplo, un servidor local).
 */
class OpenAiClient
{
    public function __construct(private string $apiKey, private string $model, private string $baseUrl) {}

    /**
     * @param  array<int,array{role:string,content:string}>  $history
     */
    public function chat(string $system, array $history): string
    {
        $messages = [['role' => 'system', 'content' => $system]];
        foreach ($history as $msg) {
            $messages[] = ['role' => $msg['role'] === 'assistant' ? 'assistant' : 'user', 'content' => $msg['content']];
        }

        $response = Http::timeout(config('bufete.ai.timeout'))
            ->withToken($this->apiKey)
            ->acceptJson()
            ->post(rtrim($this->baseUrl, '/').'/chat/completions', [
                'model' => $this->model,
                'messages' => $messages,
            ]);

        if ($response->failed()) {
            throw new AiException('OpenAI respondió con error: '.($response->json('error.message') ?? $response->status()));
        }

        $text = (string) $response->json('choices.0.message.content', '');

        if ($text === '') {
            throw new AiException('OpenAI no devolvió texto.');
        }

        return $text;
    }
}
