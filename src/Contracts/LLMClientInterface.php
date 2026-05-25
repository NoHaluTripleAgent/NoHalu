<?php

declare(strict_types=1);

namespace NHM\Contracts;

/**
 * Contract voor een LLM-backend. Elke agent in de pool wordt aangedreven door
 * een implementatie hiervan (Anthropic, OpenAI, Gemini, of Mock voor de demo).
 */
interface LLMClientInterface
{
    /**
     * Genereer een initiële respons op de prompt.
     */
    public function complete(string $prompt, float $temperature): string;

    /**
     * Genereer een adversariale challenge: probeer de gegeven respons te
     * weerleggen of te corrigeren op basis van dezelfde prompt.
     */
    public function challenge(string $prompt, string $candidateResponse, float $temperature): string;

    /**
     * Naam van het onderliggende model, voor logging/audit.
     */
    public function modelName(): string;
}
