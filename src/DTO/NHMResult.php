<?php

declare(strict_types=1);

namespace NHM\DTO;

/**
 * NHMResult — het eindresultaat dat de Orchestrator retourneert aan de
 * aanroepende applicatie. Bevat de geharmoniseerde respons en alle
 * auditmetadata. Zie technisch document §2.1 (Response Body).
 */
final class NHMResult
{
    /**
     * @param array<int, array<string, mixed>> $agentScores
     * @param array<int, float>                $divergenceTrace
     */
    public function __construct(
        public readonly string $result,
        public readonly float $confidence,
        public readonly int $iterations,
        public readonly bool $converged,
        public readonly array $agentScores,
        public readonly array $divergenceTrace,
        public readonly int $processingMs,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'result'           => $this->result,
            'confidence'       => round($this->confidence, 4),
            'iterations'       => $this->iterations,
            'converged'        => $this->converged,
            'agent_scores'     => $this->agentScores,
            'divergence_trace' => array_map(fn ($d) => round($d, 4), $this->divergenceTrace),
            'processing_ms'    => $this->processingMs,
        ];
    }

    public function toJson(int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE): string
    {
        return json_encode($this->toArray(), $flags | JSON_THROW_ON_ERROR);
    }
}
