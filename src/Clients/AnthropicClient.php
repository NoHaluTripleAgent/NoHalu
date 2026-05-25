<?php

declare(strict_types=1);

namespace NHM\Clients;

use NHM\Contracts\LLMClientInterface;
use RuntimeException;

/**
 * AnthropicClient — productie-client voor Claude (Messages API).
 * Vereist de environment-variabele met de API-key. Gebruikt cURL zonder
 * externe dependencies.
 */
final class AnthropicClient implements LLMClientInterface
{
    private const ENDPOINT = 'https://api.anthropic.com/v1/messages';
    private const VERSION = '2023-06-01';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model = 'claude-3-5-sonnet-20241022',
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
        $challengePrompt = <<<TXT
            Originele vraag:
            {$prompt}

            Een ander systeem gaf dit antwoord:
            ---
            {$candidate}
            ---
            Controleer dit antwoord kritisch op feitelijke fouten, ongefundeerde claims
            of hallucinaties. Geef een gecorrigeerd, goed onderbouwd antwoord. Als het
            antwoord correct is, bevestig het en herhaal de kern.
            TXT;

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
                'content-type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: ' . self::VERSION,
            ],
        ]);

        $raw = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new RuntimeException("Anthropic-aanroep mislukt: {$err}");
        }
        if ($status >= 400) {
            throw new RuntimeException("Anthropic API fout (HTTP {$status}): {$raw}");
        }

        $data = json_decode((string) $raw, true, 512, JSON_THROW_ON_ERROR);

        return (string) ($data['content'][0]['text'] ?? '');
    }
}
