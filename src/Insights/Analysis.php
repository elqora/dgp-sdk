<?php

namespace Elqora\Dgp\Insights;

use Elqora\Chart\Charts\Chart;
use Elqora\Dgp\Support\Arrayable;
use Elqora\Dgp\Support\StableIdentifier;
use JsonSerializable;

final readonly class Analysis implements Arrayable, JsonSerializable
{
    public function __construct(
        public string $analysisKey,
        public Chart $chart,
    ) {
        StableIdentifier::assert($this->analysisKey, 'Analysis key');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'analysis_key' => $this->analysisKey,
            'chart' => $this->chart->toArray(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
