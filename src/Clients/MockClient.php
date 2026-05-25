<?php

declare(strict_types=1);

namespace NHM\Clients;

use NHM\Contracts\LLMClientInterface;

/**
 * MockClient — simuleert een LLM-agent zodat de demo offline draait, zonder
 * API-keys. Het genereert plausibele varianten op een vooraf gedefinieerde
 * "kennisbank", met variatie afhankelijk van temperatuur. NIET voor productie.
 */
final class MockClient implements LLMClientInterface
{
    /** @param array<string, string> $knowledge promptfragment => correct antwoord */
    public function __construct(
        private readonly string $persona,
        private readonly array $knowledge = [],
        private readonly float $accuracy = 0.9,
    ) {
    }

    public function modelName(): string
    {
        return 'mock-' . $this->persona;
    }

    public function complete(string $prompt, float $temperature): string
    {
        $answer = $this->lookup($prompt);

        // Hogere temperatuur => grotere kans op variatie/ruis.
        $noise = $temperature * (1.0 - $this->accuracy);
        if ($this->roll() < $noise) {
            return $this->addUncertainty($answer);
        }

        return $this->addGrounding($answer);
    }

    public function challenge(string $prompt, string $candidate, float $temperature): string
    {
        // Een challenge herformuleert het eigen antwoord; bij overeenstemming
        // ligt het dicht bij de kandidaat (lage divergentie).
        $own = $this->lookup($prompt);

        if ($this->similarEnough($own, $candidate)) {
            return $candidate; // bevestiging => W ~ 0
        }

        return $this->addGrounding($own);
    }

    private function lookup(string $prompt): string
    {
        $needle = mb_strtolower($prompt);
        foreach ($this->knowledge as $key => $value) {
            if (str_contains($needle, mb_strtolower($key))) {
                return $value;
            }
        }

        return 'Op basis van de beschikbare informatie is hierover geen onderbouwd antwoord te geven.';
    }

    private function addGrounding(string $text): string
    {
        return 'Volgens betrouwbare bronnen: ' . $text;
    }

    private function addUncertainty(string $text): string
    {
        return $text . ' Dit is echter niet altijd met zekerheid vast te stellen.';
    }

    private function similarEnough(string $a, string $b): bool
    {
        similar_text(mb_strtolower($a), mb_strtolower($b), $percent);
        return $percent > 55.0;
    }

    private function roll(): float
    {
        return mt_rand(0, 1000) / 1000.0;
    }
}
