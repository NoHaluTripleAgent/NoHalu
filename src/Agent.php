<?php

declare(strict_types=1);

namespace NHM;

use NHM\Contracts\LLMClientInterface;
use NHM\DTO\AgentResponse;

/**
 * Agent — een individuele AI-agent in de pool (A1, A2 of A3). Elke instantie is
 * operationeel geïsoleerd: een eigen client, een eigen temperatuur, geen
 * gedeeld geheugen. Zie Schema 1 (Agent Pool) en Schema 4 (Klasse Agent).
 */
final class Agent
{
    public function __construct(
        public readonly int $id,
        private readonly LLMClientInterface $client,
        public readonly float $temperature,
    ) {
    }

    public function model(): string
    {
        return $this->client->modelName();
    }

    /** respond() — initiële inferentie op de prompt. */
    public function respond(string $prompt): AgentResponse
    {
        $text = $this->client->complete($prompt, $this->temperature);
        return new AgentResponse($this->id, $text);
    }

    /** challenge() — adversariale verificatierespons op de winnende respons. */
    public function challenge(string $prompt, string $candidate): AgentResponse
    {
        $text = $this->client->challenge($prompt, $candidate, $this->temperature);
        return new AgentResponse($this->id, $text);
    }
}
