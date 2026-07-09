<?php

namespace Elqora\Dgp\Tests\Fixtures\Repository;

use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Insights\Analysis;
use Elqora\Dgp\Insights\Contracts\HandlerInsightsRepositoryContract;
use Elqora\Dgp\Insights\Leaderboard;
use Elqora\Dgp\Insights\Scoreboard;
use Elqora\Dgp\Runtime\References\HandlerReference;
use Elqora\Dgp\Support\StableIdentifier;

class MockHandlerInsightsRepository implements HandlerInsightsRepositoryContract
{
    /**
     * @var array<string, mixed>
     */
    private array $store;
    private HandlerReference $handler;

    /**
     * @param array<string, mixed> $store
     */
    public function __construct(array &$store, HandlerReference $handler)
    {
        $this->store = &$store;
        $this->handler = $handler;
    }

    private function getHandlerValue(): string|int
    {
        return $this->handler->value;
    }

    public function updateAnalyses(array $analyses): Result
    {
        StableIdentifier::assertUnique(
            array_map(fn (Analysis $analysis) => $analysis->analysisKey, $analyses),
            'Analysis key'
        );

        $this->store['analyses'][$this->getHandlerValue()] = $analyses;

        return Result::success(null);
    }

    public function updateScoreboard(Scoreboard $scoreboard): Result
    {
        $this->store['scoreboards'][$this->getHandlerValue()] = $scoreboard;

        return Result::success(null);
    }

    public function updateLeaderboard(Leaderboard $leaderboard): Result
    {
        $this->store['leaderboards'][$this->getHandlerValue()] = $leaderboard;

        return Result::success(null);
    }

    /**
     * @return list<Analysis>
     */
    public function analysesSnapshot(): array
    {
        return $this->store['analyses'][$this->getHandlerValue()] ?? [];
    }

    public function scoreboardSnapshot(): ?Scoreboard
    {
        return $this->store['scoreboards'][$this->getHandlerValue()] ?? null;
    }

    public function leaderboardSnapshot(): ?Leaderboard
    {
        return $this->store['leaderboards'][$this->getHandlerValue()] ?? null;
    }
}
