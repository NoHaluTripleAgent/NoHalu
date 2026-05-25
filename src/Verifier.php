<?php

declare(strict_types=1);

namespace NHM;

use NHM\Contracts\EmbeddingInterface;
use NHM\DTO\AgentResponse;

/**
 * Verifier — voert de adversariale kruisverificatie uit. De twee verliezende
 * agents challengen de winnaar; de weerleggingsscore W is de cosinus-afstand
 * tussen de embeddings:
 *
 *   W(Ai, A*) = 1 - sim(embed(Ri), embed(R*))
 *   D(n)      = max(W)
 *
 * Zie wetenschappelijk document §2.3 en technisch document §3.3.
 */
final class Verifier
{
    public function __construct(
        private readonly EmbeddingInterface $embedding,
    ) {
    }

    /**
     * @param array<int, Agent> $challengers
     * @return array{divergence: float, challenges: array<int, AgentResponse>}
     */
    public function verify(string $prompt, AgentResponse $winner, array $challengers): array
    {
        $challenges = [];
        $wScores = [];

        foreach ($challengers as $agent) {
            $challenge = $agent->challenge($prompt, $winner->text);
            $challenges[] = $challenge;

            $similarity = $this->embedding->similarity($challenge->text, $winner->text);
            $wScores[] = 1.0 - $similarity;
        }

        return [
            'divergence' => $wScores === [] ? 0.0 : max($wScores),
            'challenges' => $challenges,
        ];
    }
}
