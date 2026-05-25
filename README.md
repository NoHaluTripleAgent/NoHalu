# No-Halu Model (NHM)

> **Triple-Agent Consensus Architecture for Hallucination Reduction**

Het **No-Halu Model** is een raamwerk dat hallucinaties in grote taalmodellen (LLMs) reduceert door drie onafhankelijke AI-agents dezelfde vraag te laten beantwoorden, hun antwoorden te scoren, ze elkaar te laten controleren (adversariale kruisverificatie) en de beste componenten iteratief samen te voegen tot één geharmoniseerd antwoord.

> **Belangrijk:** "No-Halu" staat voor *Low Hallucination*. Het model verlaagt het risico op hallucinaties aanzienlijk, maar **geen enkel systeem sluit hallucinaties volledig uit**. Bij kritische toepassingen blijft menselijke verificatie altijd vereist.

---

## Hoe het werkt

```
Prompt
  │
  ▼
[0] Preprocessing   → sanitatie, domeindetectie, gewichtskalibratie
[1] Parallelle inferentie → Agent 1, 2, 3 (geïsoleerd, eigen temperatuur)
[2] Scoring          → S(R) = α·C + β·F + γ·Coh + δ·Src
[3] Winnaar-selectie → A* = argmax(S)
[4] Kruisverificatie → verliezers challengen A*, W = 1 − sim(embeds)
[5] Harmonisatie     → consensus-zinnen samenvoegen
[6] Convergentiecheck → D(n) < ε ?  ── nee ──► herhaal (max 10x)
                              │ ja
                              ▼
                         Geharmoniseerd antwoord + auditmetadata
```

De scoringsformule weegt vier dimensies (standaardgewichten tussen haakjes):

| Dimensie | Symbool | Wat het meet | Gewicht |
|----------|---------|--------------|---------|
| Interne consistentie | C(R) | Tegenstrijdigheden binnen het antwoord | 0.30 |
| Factual density | F(R) | Aandeel verifieerbare claims | 0.30 |
| Semantische coherentie | Coh(R) | Aansluiting bij de promptintentie | 0.25 |
| Bronverankering | Src(R) | Aanwezigheid van bronverwijzingen | 0.15 |

De volledige onderbouwing staat in [`docs/`](docs/) (wetenschappelijk + technisch document en vier schema's).

---

## Snel starten (geen API-keys nodig)

De demo draait met gesimuleerde agents, zodat je de pijplijn meteen kunt zien werken:

```bash
git clone https://github.com/<jouw-gebruikersnaam>/no-halu-model.git
cd no-halu-model
php demo/demo.php
```

Vereisten: PHP 8.1+ met de extensies `mbstring` en `curl`.

---

## Gebruik met echte LLM's (productie)

1. Kopieer `.env.example` naar `.env` en vul je API-keys in.
2. Vervang in je eigen code de `MockClient`-instanties door echte clients:

```php
use NHM\Agent;
use NHM\Clients\{AnthropicClient, OpenAIClient, GeminiClient};
use NHM\{Orchestrator, ScoringEngine, Verifier, Harmonizer};
use NHM\Support\JaccardEmbedding;
use NHM\Config\Weights;

$embedding = new JaccardEmbedding(); // vervang door echt embedding-model in productie
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

$result = $nhm->query('Jouw vraag hier');
echo $result->toJson();
```

> Het meegeleverde `JaccardEmbedding` is een lichtgewicht proxy voor de demo. Voor productie wordt een echt embedding-model aanbevolen (bijv. `all-MiniLM-L6-v2`); implementeer daarvoor de `EmbeddingInterface`.

---

## Projectstructuur

```
src/
  Orchestrator.php        # centrale controller + convergentielus
  Agent.php               # individuele agent (respond / challenge)
  ScoringEngine.php       # S(R) over vier dimensies
  Verifier.php            # adversariale kruisverificatie
  Harmonizer.php          # consensus-merge
  Contracts/              # interfaces (LLM-client, embedding)
  Clients/                # Anthropic, OpenAI, Gemini, Mock
  DTO/                    # AgentResponse, ScoreResult, NHMResult
  Config/                 # Weights
  Support/                # JaccardEmbedding
demo/demo.php             # draaibare demo zonder API-keys
tests/                    # PHPUnit-tests
docs/                     # wetenschappelijk + technisch document, schema's
```

---

## Beperkingen

- **Computationele overhead:** drie parallelle inferenties verdrievoudigen de kosten.
- **Latentie:** gemiddeld ~2.7× trager dan één enkele aanroep.
- **Circulaire hallucinaties:** als alle drie agents dezelfde fout maken, faalt de correctie.
- **Domeinkalibratie:** de gewichtsvectoren vragen domeinspecifieke afstemming.

De prestatiecijfers in het wetenschappelijk document zijn gebaseerd op een **conceptuele/synthetische benchmark** en vormen geen onafhankelijk gevalideerde claim.

---

## Licentie

Code: [MIT](LICENSE). De documenten in `docs/` mogen vrij gedeeld worden met bronvermelding.

## Bijdragen

Zie [CONTRIBUTING.md](CONTRIBUTING.md).
