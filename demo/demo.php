<?php

declare(strict_types=1);

/**
 * NHM Demo — toont het complete No-Halu Model in werking met drie gesimuleerde
 * agents (MockClient). Draait zonder API-keys:
 *
 *     php demo/demo.php
 *
 * Voor een echte run met live LLM's: vervang de MockClients door
 * AnthropicClient / OpenAIClient / GeminiClient (zie README §Productie).
 */

require __DIR__ . '/../autoload.php';

use NHM\Agent;
use NHM\Clients\MockClient;
use NHM\Config\Weights;
use NHM\Harmonizer;
use NHM\Orchestrator;
use NHM\ScoringEngine;
use NHM\Support\JaccardEmbedding;
use NHM\Verifier;

// Gedeelde "kennisbank" die de gesimuleerde agents raadplegen.
$knowledge = [
    'hoofdstad van frankrijk' => 'De hoofdstad van Frankrijk is Parijs, gelegen aan de Seine.',
    'kookt water'             => 'Water kookt bij 100 graden Celsius op zeeniveau (1 atmosfeer druk).',
    'snelheid van het licht'  => 'De snelheid van het licht in vacuüm is ongeveer 299.792 kilometer per seconde.',
];

// Drie operationeel geïsoleerde agents met variërende temperatuur (Schema 1).
$embedding = new JaccardEmbedding();
$scoring   = new ScoringEngine($embedding, new Weights());
$verifier  = new Verifier($embedding);
$harmonizer = new Harmonizer($embedding);

$agents = [
    new Agent(1, new MockClient('A', $knowledge, accuracy: 0.95), temperature: 0.65),
    new Agent(2, new MockClient('B', $knowledge, accuracy: 0.90), temperature: 0.70),
    new Agent(3, new MockClient('C', $knowledge, accuracy: 0.85), temperature: 0.75),
];

$orchestrator = new Orchestrator(
    agents: $agents,
    scoringEngine: $scoring,
    verifier: $verifier,
    harmonizer: $harmonizer,
    maxIterations: 10,
    epsilon: 0.05,
);

$prompts = [
    'Wat is de hoofdstad van Frankrijk?',
    'Bij welke temperatuur kookt water?',
    'Wat is de snelheid van het licht?',
];

$line = str_repeat('=', 64);

echo "\n{$line}\n  NO-HALU MODEL — DEMO (gesimuleerde agents)\n{$line}\n";

foreach ($prompts as $prompt) {
    $result = $orchestrator->query($prompt);

    echo "\nPrompt:      {$prompt}\n";
    echo "Antwoord:    {$result->result}\n";
    echo sprintf("Confidence:  %.2f%%\n", $result->confidence * 100);
    echo sprintf("Iteraties:   %d\n", $result->iterations);
    echo 'Convergentie: ' . ($result->converged ? 'JA' : 'NEE') . "\n";
    echo 'Divergentie:  [' . implode(', ', array_map(
        static fn ($d) => number_format($d, 3),
        $result->divergenceTrace
    )) . "]\n";
    echo sprintf("Verwerktijd: %d ms\n", $result->processingMs);
    echo str_repeat('-', 64) . "\n";
}

echo "\nVolledig JSON-resultaat van de laatste query:\n";
echo $result->toJson() . "\n\n";

echo "Herinnering: 'No-Halu' = Low Hallucination. Geen enkel systeem sluit\n";
echo "hallucinaties volledig uit; menselijke verificatie blijft vereist.\n\n";
