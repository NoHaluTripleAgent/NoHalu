# No-Halu Model (NHM)

> **Triple-Agent Consensus Architecture for Hallucination Reduction**

The **No-Halu Model** is a framework that reduces hallucinations in large language models (LLMs) by having three independent AI agents answer the same question, scoring their responses, letting them verify each other (adversarial cross-verification), and iteratively merging the best components into a single harmonized answer.

> **Important:** "No-Halu" stands for *Low Hallucination*. The model significantly lowers the risk of hallucinations, but **no system eliminates hallucinations entirely**. Human verification remains essential for critical use cases.

---

## How it works

```
Prompt
  │
  ▼
[0] Preprocessing      → sanitization, domain detection, weight calibration
[1] Parallel inference → Agent 1, 2, 3 (isolated, own temperature)
[2] Scoring            → S(R) = α·C + β·F + γ·Coh + δ·Src
[3] Winner selection   → A* = argmax(S)
[4] Cross-verification → losers challenge A*, W = 1 − sim(embeds)
[5] Harmonization      → merge consensus sentences
[6] Convergence check  → D(n) < ε ?  ── no ──► repeat (max 10x)
                              │ yes
                              ▼
                         Harmonized answer + audit metadata
```

The scoring formula weighs four dimensions (default weights in parentheses):

| Dimension | Symbol | What it measures | Weight |
|-----------|--------|------------------|--------|
| Internal consistency | C(R) | Contradictions within the answer | 0.30 |
| Factual density | F(R) | Share of verifiable claims | 0.30 |
| Semantic coherence | Coh(R) | Alignment with the prompt intent | 0.25 |
| Source grounding | Src(R) | Presence of source references | 0.15 |

The full reasoning is documented in [`docs/`](docs/) (scientific + technical document and four diagrams).

---

## Quick start (no API keys needed)

The demo runs with simulated agents, so you can see the pipeline work immediately:

```bash
git clone https://github.com/NoHaluTripleAgent/NoHalu.git
cd NoHalu
php demo/demo.php
```

Requirements: PHP 8.1+ with the `mbstring` and `curl` extensions.

---

## Usage with real LLMs (production)

1. Copy `.env.example` to `.env` and fill in your API keys.
2. In your own code, replace the `MockClient` instances with real clients:

```php
use NHM\Agent;
use NHM\Clients\{AnthropicClient, OpenAIClient, GeminiClient};
use NHM\{Orchestrator, ScoringEngine, Verifier, Harmonizer};
use NHM\Support\JaccardEmbedding;
use NHM\Config\Weights;

$embedding = new JaccardEmbedding(); // replace with a real embedding model in production
$agents = [
    new Agent(1, new AnthropicClient(getenv('NHM_AGENT1_API_KEY')), 0.65),
    new Agent(2, new OpenAIClient(getenv('NHM_AGENT2_API_KEY')),    0.70),
    new Agent(3, new GeminiClient(getenv('NHM_AGENT3_API_KEY')),    0.75),
];

$nhm = new Orchestrator(
    $agents,
    new ScoringEngine($embedding, Weights::forDomain('medical')),
    new Verifier($embedding),
    new Harmonizer($embedding),
    maxIterations: 10,
    epsilon: 0.05,
);

$result = $nhm->query('Your question here');
echo $result->toJson();
```

> The bundled `JaccardEmbedding` is a lightweight proxy for the demo. For production a real embedding model is recommended (e.g. `all-MiniLM-L6-v2`); implement the `EmbeddingInterface` for that.

---

## Project structure

```
src/
  Orchestrator.php        # central controller + convergence loop
  Agent.php               # individual agent (respond / challenge)
  ScoringEngine.php       # S(R) across four dimensions
  Verifier.php            # adversarial cross-verification
  Harmonizer.php          # consensus merge
  Contracts/              # interfaces (LLM client, embedding)
  Clients/                # Anthropic, OpenAI, Gemini, Mock
  DTO/                    # AgentResponse, ScoreResult, NHMResult
  Config/                 # Weights
  Support/                # JaccardEmbedding
demo/demo.php             # runnable demo without API keys
tests/                    # PHPUnit tests
docs/                     # scientific + technical document, diagrams
```

---

## Limitations

- **Computational overhead:** three parallel inferences triple the cost.
- **Latency:** on average ~2.7× slower than a single call.
- **Circular hallucinations:** if all three agents make the same mistake, the correction fails.
- **Domain calibration:** the weight vectors require domain-specific tuning.

The performance figures in the scientific document are based on a **conceptual/synthetic benchmark** and do not constitute an independently validated claim.

---

## License

Code: [MIT](LICENSE). The documents in `docs/` may be freely shared with attribution.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).
