<?php

declare(strict_types=1);

namespace NHM\Tests;

use NHM\Config\Weights;
use NHM\ScoringEngine;
use NHM\Support\JaccardEmbedding;
use PHPUnit\Framework\TestCase;

final class ScoringEngineTest extends TestCase
{
    private function engine(): ScoringEngine
    {
        return new ScoringEngine(new JaccardEmbedding(), new Weights());
    }

    public function testScoreIsWithinUnitInterval(): void
    {
        $result = $this->engine()->calculate(
            'According to research, water boils at 100 degrees Celsius at sea level.',
            'At what temperature does water boil?'
        );

        self::assertGreaterThanOrEqual(0.0, $result->total);
        self::assertLessThanOrEqual(1.0, $result->total);
    }

    public function testGroundingMarkersRaiseGroundingScore(): void
    {
        $engine = $this->engine();
        $grounded = $engine->measureGrounding('According to peer-reviewed studies indicate this is true.');
        $bare = $engine->measureGrounding('This is just an opinion.');

        self::assertGreaterThan($bare, $grounded);
    }

    public function testAbsolutismMarkersLowerFactuality(): void
    {
        $engine = $this->engine();
        $withAbsolutism = $engine->measureFactuality('This is always 100% certain and guaranteed in 2024.');
        $neutral = $engine->measureFactuality('The study from 2024 reported a 35% increase.');

        self::assertLessThan($neutral, $withAbsolutism);
    }

    public function testShortResponseIsPenalisedOnConsistency(): void
    {
        $engine = $this->engine();
        $short = $engine->measureConsistency('Yes.');

        self::assertLessThan(1.0, $short);
    }

    public function testWeightsMustSumToOne(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Weights(0.5, 0.5, 0.5, 0.5);
    }
}
