<?php

declare(strict_types=1);

namespace NHM\Clients;

use NHM\Contracts\LLMClientInterface;
use RuntimeException;

/**
 * GeminiClient — productie-client voor Google Gemini (generateContent API).
 */
final class GeminiClient implements LLMClientInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model = 'gemini-1.5-pro',
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
            . "Controleer kritisch op fouten of hallucinaties en geef een onderbouwd antwoord.";

        return $this->request($challengePrompt, $temperature);
    }

    private function request(string $prompt, float $temperature): string
    {
        $endpoint = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            rawurlencode($this->model),
            rawurlencode($this->apiKey),
        );

        $payload = json_encode([
            'contents'         => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'temperature'     => $temperature,
                'maxOutputTokens' => $this->maxTokens,
            ],
        ], JSON_THROW_ON_ERROR);

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        ]);

        $raw = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new RuntimeException("Gemini-aanroep mislukt: {$err}");
        }
        if ($status >= 400) {
            throw new RuntimeException("Gemini API fout (HTTP {$status}): {$raw}");
        }

        $data = json_decode((string) $raw, true, 512, JSON_THROW_ON_ERROR);

        return (string) ($data['candidates'][0]['content']['parts'][0]['text'] ?? '');
    }
}
