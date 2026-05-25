<?php

declare(strict_types=1);

namespace NHM\Contracts;

/**
 * Contract voor het embedding-model dat gebruikt wordt bij de kruisverificatie
 * (cosinus-afstand tussen responsen). In productie een lokaal model zoals
 * all-MiniLM-L6-v2; in de demo een lichtgewicht Jaccard/keyword-proxy.
 *
 * Zie technisch document §3.3 en wetenschappelijk document §2.3.
 */
interface EmbeddingInterface
{
    /**
     * Semantische similariteit tussen twee teksten, genormaliseerd naar [0, 1].
     * 1.0 = identiek, 0.0 = volledig verschillend.
     */
    public function similarity(string $a, string $b): float;
}
