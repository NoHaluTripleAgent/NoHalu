<?php

declare(strict_types=1);

namespace NHM\DTO;

/**
 * ScoreResult — bevat de totaalscore S(R) plus de vier deelscores per dimensie.
 *
 * Zie wetenschappelijk document §2.2 en Schema 2 (Scoringsformule):
 *   S(R) = a·C(R) + b·F(R) + g·Coh(R) + d·Src(R)
 */
final class ScoreResult
{
    public function __construct(
        public readonly float $total,
        public readonly float $consistency, // C(R)
        public readonly float $factuality,  // F(R)
        public readonly float $coherence,   // Coh(R)
        public readonly float $grounding,   // Src(R)
    ) {
    }

    /** @return array<string, float> */
    public function toArray(): array
    {
        return [
            'total'       => round($this->total, 4),
            'consistency' => round($this->consistency, 4),
            'factuality'  => round($this->factuality, 4),
            'coherence'   => round($this->coherence, 4),
            'grounding'   => round($this->grounding, 4),
        ];
    }
}
