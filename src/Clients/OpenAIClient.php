<?php

declare(strict_types=1);

namespace NHM\Clients;

use NHM\Contracts\LLMClientInterface;
use RuntimeException;

/**
 * OpenAIClient — productie-client voor GPT-modellen (Chat Completions API).
 */
final class OpenAIClient implements LLMClientInterface
{
    private const ENDPOINT = 'https://api.openai.com/v1/chat/completions';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model = 'gpt-4o',
        private readonly int $maxTokens = 1024,
    ) {
    }

    public function modelName(): string
    {
        return $this->model;
    }

    public function complete(string $prompt, float $temperature): string
    {
        return $this->request($prompt, $temperature);
    }

    public function challenge(string $prompt, string $candidate, float $temperature): string
    {
        $challengePrompt = "Originele vraag:\n{$prompt}\n\n"
            . "Een ander systeem antwoordde:\n---\n{$candidate}\n---\n"
            . "Controleer kritisch op feitelijke fouten of hallucinaties en geef een "
            . "gecorrigeerd, onderbouwd antwoord.";

        return $this->request($challengePrompt, $temperature);
    }

    private function request(string $prompt, float $temperature): string
    {
        $payload = json_encode([
            'model'       => $this->model,
            'max_tokens'  => $this->maxTokens,
            'temperature' => $temperature,
            'messages'    => [['role' => 'user', 'content' => $prompt]],
        ], JSON_THROW_ON_ERROR);

        $ch = curl_init(self::ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
        ]);

        $raw = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new RuntimeException("OpenAI-aanroep mislukt: {$err}");
        }
        if ($status >= 400) {
            throw new RuntimeException("OpenAI API fout (HTTP {$status}): {$raw}");
        }

        $data = json_decode((string) $raw, true, 512, JSON_THROW_ON_ERROR);

        return (string) ($data['choices'][0]['message']['content'] ?? '');
    }
}
