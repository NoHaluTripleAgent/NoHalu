<?php

declare(strict_types=1);

namespace NHM;

use NHM\Config\Weights;
use NHM\Contracts\EmbeddingInterface;
use NHM\DTO\ScoreResult;

/**
 * ScoringEngine — berekent de vertrouwensscore S(R) op vier dimensies.
 *
 *   S(R) = a·C(R) + b·F(R) + g·Coh(R) + d·Src(R)
 *
 * De numerieke regels (sancties, baselines) volgen exact Schema 2 §Dimensies 1-4.
 */
final class ScoringEngine
{
    /** Contradictie-indicatoren die C(R) verlagen (-0.15 per stuk). */
    private const CONTRADICTION_MARKERS = [
        'however this contradicts', 'but also states the opposite',
        'on the contrary', 'this is inconsistent', 'maar tegelijk het tegenovergestelde',
        'dit spreekt zichzelf tegen', 'enerzijds', 'anderzijds tegenstrijdig',
    ];

    /** Absolutisme-markers die F(R) verlagen (-0.10 per stuk). */
    private const ABSOLUTISM_MARKERS = [
        'always', 'never', '100% certain', 'guaranteed', 'without any doubt',
        'altijd', 'nooit', 'gegarandeerd', 'absoluut zeker',
    ];

    /** Grondingsindicatoren die Src(R) verhogen (+0.13 per stuk). */
    private const GROUNDING_MARKERS = [
        'according to', 'research shows', 'studies indicate', 'peer-reviewed',
        'volgens', 'onderzoek toont', 'studies wijzen', 'bron:',
    ];

    public function __construct(
        private readonly EmbeddingInterface $embedding,
        private readonly Weights $weights = new Weights(),
    ) {
    }

    public function calculate(string $response, string $prompt): ScoreResult
    {
        $c = $this->measureConsistency($response);
        $f = $this->measureFactuality($response);
        $coh = $this->measureCoherence($response, $prompt);
        $src = $this->measureGrounding($response);

        $total =
            $this->weights->alpha * $c +
            $this->weights->beta * $f +
            $this->weights->gamma * $coh +
            $this->weights->delta * $src;

        return new ScoreResult($total, $c, $f, $coh, $src);
    }

    /** C(R) — interne consistentie. Baseline 1.0, -0.15 per indicator, korte respons gesanctioneerd. */
    public function measureConsistency(string $response): float
    {
        $score = 1.0;
        $haystack = mb_strtolower($response);

        foreach (self::CONTRADICTION_MARKERS as $marker) {
            if (str_contains($haystack, $marker)) {
                $score -= 0.15;
            }
        }

        // Extreem korte responsen (< 10 woorden) zijn verdacht.
        if ($this->wordCount($response) < 10) {
            $score -= 0.30;
        }

        return $this->clamp($score);
    }

    /** F(R) — factual density. Verhouding verifieerbare claims, -0.10 per absolutisme-marker. */
    public function measureFactuality(string $response): float
    {
        $sentences = $this->sentences($response);
        if ($sentences === []) {
            return 0.0;
        }

        $verifiable = 0;
        foreach ($sentences as $sentence) {
            // Een zin telt als 'verankerd' bij cijfers, jaren, maten of bronverwijzing.
            if (preg_match('/\d|%|(19|20)\d{2}|according to|volgens|research|onderzoek/iu', $sentence)) {
                $verifiable++;
            }
        }

        $score = $verifiable / count($sentences);

        $haystack = mb_strtolower($response);
        foreach (self::ABSOLUTISM_MARKERS as $marker) {
            if (preg_match('/\b' . preg_quote($marker, '/') . '\b/u', $haystack)) {
                $score -= 0.10;
            }
        }

        return $this->clamp($score);
    }

    /** Coh(R) — semantische coherentie t.o.v. de promptintentie. */
    public function measureCoherence(string $response, string $prompt): float
    {
        return $this->clamp($this->embedding->similarity($response, $prompt));
    }

    /** Src(R) — bronverankering. Baseline 0.20, +0.13 per indicator. */
    public function measureGrounding(string $response): float
    {
        $score = 0.20;
        $haystack = mb_strtolower($response);

        foreach (self::GROUNDING_MARKERS as $marker) {
            if (str_contains($haystack, $marker)) {
                $score += 0.13;
            }
        }

        return $this->clamp($score);
    }

    private function clamp(float $v): float
    {
        return max(0.0, min(1.0, $v));
    }

    private function wordCount(string $text): int
    {
        $words = preg_split('/\s+/u', trim($text)) ?: [];
        return count(array_filter($words));
    }

    /** @return array<int, string> */
    private function sentences(string $text): array
    {
        $parts = preg_split('/(?<=[.!?])\s+/u', trim($text)) ?: [];
        return array_values(array_filter($parts, static fn ($s) => trim($s) !== ''));
    }
}
