<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Service\Metrics;

use Prometheus\Counter;
use Prometheus\Gauge;
use Prometheus\Histogram;
use Prometheus\MetricFamilySamples;

interface AdapterInterface
{
    public function createCounter(string $name, string $help = '', array $labels = []): Counter;

    public function createGauge(string $name, string $help = '', array $labels = []): Gauge;

    /**
     * @param list<string>     $labels
     * @param list<float>|null $buckets
     */
    public function createHistogram(string $name, string $help = '', array $labels = [], ?array $buckets = null): Histogram;

    /**
     * @return MetricFamilySamples[]
     */
    public function collect(): array;
}
