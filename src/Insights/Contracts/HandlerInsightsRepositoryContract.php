<?php

namespace Elqora\Dgp\Insights\Contracts;

use Elqora\Dgp\Errors\Result;
use Elqora\Dgp\Insights\Analysis;
use Elqora\Dgp\Insights\Leaderboard;
use Elqora\Dgp\Insights\Scoreboard;

interface HandlerInsightsRepositoryContract
{
    /**
     * @param list<Analysis> $analyses
     * @return Result<null>
     */
    public function updateAnalyses(array $analyses): Result;

    /**
     * @param Scoreboard $scoreboard
     * @return Result<null>
     */
    public function updateScoreboard(Scoreboard $scoreboard): Result;

    /**
     * @param Leaderboard $leaderboard
     * @return Result<null>
     */
    public function updateLeaderboard(Leaderboard $leaderboard): Result;
}
