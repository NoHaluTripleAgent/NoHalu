<?php

declare(strict_types=1);

namespace NHM;

use NHM\Contracts\EmbeddingInterface;
use NHM\DTO\AgentResponse;

/**
 * Harmonizer — integreert de superieure componenten uit alle responsen.
 * Consensus-zinnen (zinnen die in ten minste twee responsen inhoudelijk
 * overeenkomen) krijgen prioriteit. Zie Schema 1 (Harmonizer) en §2.4.
 *
 *   R_harm = merge(argmax(S(Ri)), extract_verified(W))
 */
final class Harmonizer
{
    private const CONSENSUS_THRESHOLD = 0.6;

    public function __construct(
        private readonly EmbeddingInterface $embedding,
    ) {
    }

    /**
     * @param array<int, AgentResponse> $responses alle responsen (winnaar + challenges)
     */
    public function merge(AgentResponse $winner, array $responses): string
    {
        $winnerSentences = $this->sentences($winner->text);
        if ($winnerSentences === []) {
            return $winner->text;
        }

        // Verzamel zinnen uit alle overige responsen voor consensusbepaling.
        $otherSentences = [];
        foreach ($responses as $response) {
            if ($response === $winner) {
                continue;
            }
            foreach ($this->sentences($response->text) as $s) {
                $otherSentences[] = $s;
            }
        }

        $consensus = [];
        $remainder = [];

        foreach ($winnerSentences as $sentence) {
            if ($this->hasSupport($sentence, $otherSentences)) {
                $consensus[] = $sentence;
            } else {
                $remainder[] = $sentence;
            }
        }

        // Consensus-zinnen eerst, daarna de unieke bijdrage van de winnaar.
        $ordered = array_merge($consensus, $remainder);

        return implode(' ', $ordered);
    }

    /** @return string[] */
    public function extractConsensus(AgentResponse $winner, array $responses): array
    {
        $winnerSentences = $this->sentences($winner->text);
        $others = [];
        foreach ($responses as $response) {
            if ($response === $winner) {
                continue;
            }
            $others = array_merge($others, $this->sentences($response->text));
        }

        return array_values(array_filter(
            $winnerSentences,
            fn (string $s) => $this->hasSupport($s, $others)
        ));
    }

    /** @param array<int, string> $pool */
    private function hasSupport(string $sentence, array $pool): bool
    {
        foreach ($pool as $candidate) {
            if ($this->embedding->similarity($sentence, $candidate) >= self::CONSENSUS_THRESHOLD) {
                return true;
            }
        }
        return false;
    }

    /** @return array<int, string> */
    private function sentences(string $text): array
    {
        $parts = preg_split('/(?<=[.!?])\s+/u', trim($text)) ?: [];
        return array_values(array_filter($parts, static fn ($s) => trim($s) !== ''));
    }
}
