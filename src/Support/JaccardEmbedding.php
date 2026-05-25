<?php

declare(strict_types=1);

namespace NHM\Support;

use NHM\Contracts\EmbeddingInterface;

/**
 * Vereenvoudigde similariteitsmaatstaf op basis van Jaccard-overeenkomst van
 * sleutelwoorden. Dit is de demo-proxy die in Schema 2 (Coh(R)) en het
 * technisch document §3.3 wordt genoemd: gebruikt wanneer een echt
 * embedding-model niet beschikbaar is.
 */
final class JaccardEmbedding implements EmbeddingInterface
{
    public function similarity(string $a, string $b): float
    {
        $setA = $this->tokens($a);
        $setB = $this->tokens($b);

        if ($setA === [] && $setB === []) {
            return 1.0;
        }

        $intersection = count(array_intersect($setA, $setB));
        $union = count(array_unique(array_merge($setA, $setB)));

        return $union === 0 ? 0.0 : $intersection / $union;
    }

    /** @return array<int, string> unieke, genormaliseerde tokens */
    private function tokens(string $text): array
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text) ?? '';
        $words = preg_split('/\s+/u', trim($text)) ?: [];
        $words = array_filter($words, static fn ($w) => mb_strlen($w) > 2);

        return array_values(array_unique($words));
    }
}
