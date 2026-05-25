<?php

declare(strict_types=1);

namespace NHM\Config;

use InvalidArgumentException;

/**
 * Gewichtsvectoren voor de vier scoringsdimensies. De som moet 1.0 zijn
 * (zie wetenschappelijk document §2.2 en Schema 2).
 *
 * Standaard: 0.30 / 0.30 / 0.25 / 0.15
 */
final class Weights
{
    public function __construct(
        public readonly float $alpha = 0.30, // C(R)  — interne consistentie
        public readonly float $beta = 0.30,  // F(R)  — factual density
        public readonly float $gamma = 0.25, // Coh(R) — semantische coherentie
        public readonly float $delta = 0.15, // Src(R) — bronverankering
    ) {
        $sum = $this->alpha + $this->beta + $this->gamma + $this->delta;
        if (abs($sum - 1.0) > 1e-6) {
            throw new InvalidArgumentException(
                sprintf('Gewichtensom moet 1.0 zijn, kreeg %.4f', $sum)
            );
        }
    }

    /**
     * Domeinspecifieke kalibratie (zie procesflow Stap 0).
     */
    public static function forDomain(string $domain): self
    {
        return match ($domain) {
            // Medisch/juridisch: zwaarder op factualiteit en bronverankering.
            'medical', 'legal' => new self(0.30, 0.35, 0.15, 0.20),
            // Technisch/wiskundig: zwaarder op interne consistentie.
            'technical'        => new self(0.40, 0.30, 0.20, 0.10),
            default            => new self(),
        };
    }
}
