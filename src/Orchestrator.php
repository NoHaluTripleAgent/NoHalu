<?php

declare(strict_types=1);

namespace NHM;

use NHM\DTO\AgentResponse;
use NHM\DTO\NHMResult;

/**
 * Orchestrator — de centrale controller. Beheert de agentcycli, aggregeert
 * scores, coördineert kruisverificatie en harmonisatie, en bewaakt de
 * convergentieconditie. Volgt de procesflow uit Schema 3 (Stap 0 t/m 6).
 */
final class Orchestrator
{
    /**
     * @param array<int, Agent> $agents minimaal 1, standaard 3
     */
    public function __construct(
        private readonly array $agents,
        private readonly ScoringEngine $scoringEngine,
        private readonly Verifier $verifier,
        private readonly Harmonizer $harmonizer,
        private readonly int $maxIterations = 10,
        private readonly float $epsilon = 0.05,
    ) {
        if (count($this->agents) < 1) {
            throw new \InvalidArgumentException('Minstens één agent vereist.');
        }
    }

    public function query(string $prompt): NHMResult
    {
        $start = hrtime(true);

        // Stap 0 — Preprocessing.
        $workingPrompt = $this->sanitize($prompt);

        $divergenceTrace = [];
        $converged = false;
        $iteration = 0;
        $harmonized = '';
        $lastScores = [];
        $bestScore = 0.0;

        do {
            $iteration++;

            // Stap 1 — Parallelle inferentie (hier sequentieel; zie README voor async).
            $responses = [];
            foreach ($this->agents as $agent) {
                $responses[] = $agent->respond($workingPrompt);
            }

            // Stap 2 — Scoring-evaluatie.
            foreach ($responses as $response) {
                $breakdown = $this->scoringEngine->calculate($response->text, $workingPrompt);
                $response->score = $breakdown->total;
                $response->scoreBreakdown = $breakdown;
            }

            // Stap 3 — Winnaar-selectie: argmax(S). Bij gelijkspel wint laagste temperatuur.
            $winner = $this->selectWinner($responses);
            $winner->isWinner = true;
            $bestScore = $winner->score;
            $lastScores = array_map(static fn (AgentResponse $r) => $r->toArray(), $responses);

            // Stap 4 — Kruisverificatie (alleen zinvol bij ≥ 2 agents).
            $challengers = array_values(array_filter(
                $this->agents,
                static fn (Agent $a) => $a->id !== $winner->agentId
            ));

            if ($challengers === []) {
                $harmonized = $winner->text;
                $divergenceTrace[] = 0.0;
                $converged = true;
                break;
            }

            $verification = $this->verifier->verify($workingPrompt, $winner, $challengers);
            $divergence = $verification['divergence'];
            $divergenceTrace[] = $divergence;

            // Stap 5 — Harmonisatie.
            $allResponses = array_merge([$winner], $verification['challenges']);
            $harmonized = $this->harmonizer->merge($winner, $allResponses);

            // Stap 6 — Convergentiecheck: D(n) < epsilon ?
            if ($divergence < $this->epsilon) {
                $converged = true;
                break;
            }

            // Niet geconvergeerd: gebruik de geharmoniseerde respons als nieuwe basis.
            $workingPrompt = $this->sanitize($prompt) . "\n\nVorige consensus: " . $harmonized;
        } while ($iteration < $this->maxIterations);

        $elapsedMs = (int) round((hrtime(true) - $start) / 1_000_000);

        // Confidence = beste score, licht verlaagd als niet geconvergeerd.
        $confidence = $converged ? $bestScore : $bestScore * 0.85;

        return new NHMResult(
            result: $harmonized,
            confidence: $confidence,
            iterations: $iteration,
            converged: $converged,
            agentScores: $lastScores,
            divergenceTrace: $divergenceTrace,
            processingMs: $elapsedMs,
        );
    }

    /** @param array<int, AgentResponse> $responses */
    private function selectWinner(array $responses): AgentResponse
    {
        usort($responses, function (AgentResponse $a, AgentResponse $b): int {
            if (abs($a->score - $b->score) < 1e-9) {
                // Gelijkspel: agent met laagste temperatuur (hoogste determinisme).
                return $this->temperatureOf($a->agentId) <=> $this->temperatureOf($b->agentId);
            }
            return $b->score <=> $a->score;
        });

        return $responses[0];
    }

    private function temperatureOf(int $agentId): float
    {
        foreach ($this->agents as $agent) {
            if ($agent->id === $agentId) {
                return $agent->temperature;
            }
        }
        return 1.0;
    }

    /** Stap 0 — promptsanitatie: tags strippen, normaliseren, lengte begrenzen (8192 tokens ≈ 32k tekens). */
    private function sanitize(string $prompt): string
    {
        $clean = strip_tags($prompt);
        $clean = preg_replace('/\s+/u', ' ', $clean) ?? $clean;
        $clean = trim($clean);

        return mb_substr($clean, 0, 32_000);
    }
}
