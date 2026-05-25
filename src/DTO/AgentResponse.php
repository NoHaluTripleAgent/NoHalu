<?php

declare(strict_types=1);

namespace NHM\DTO;

/**
 * AgentResponse — zuiver data-object dat de uitkomst van een agent-inferentie
 * of challenge-respons vastlegt. Heeft geen eigen gedrag (geen methoden die
 * de toestand wijzigen). Zie Schema 4 (Semantisch Ontologie-Model).
 */
final class AgentResponse
{
    public function __construct(
        public readonly int $agentId,
        public readonly string $text,
        public float $score = 0.0,
        public ?ScoreResult $scoreBreakdown = null,
        public bool $isWinner = false,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'agentId'        => $this->agentId,
            'text'           => $this->text,
            'score'          => round($this->score, 4),
            'scoreBreakdown' => $this->scoreBreakdown?->toArray(),
            'isWinner'       => $this->isWinner,
        ];
    }
}
