<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Service\Metrics\Adapters\Redis;

use IWD\SymfonyAppCommon\Service\Metrics\AdapterInterface;
use Prometheus\Counter;
use Prometheus\Gauge;
use Prometheus\Histogram;
use Prometheus\MetricFamilySamples;

readonly class Adapter implements AdapterInterface
{
    private \Prometheus\Storage\Redis $adapter;

    public function __construct(
        private Config $config
    ) {
        $this->adapter = new \Prometheus\Storage\Redis([
            'host' => $config->host,
        ]);
    }

    /**
     * iwd-metrics-sidecar — общий Redis на все монолиты (METRICS_SIDECAR_HOST).
     * ::collect() отдаёт все namespace'ы, лежащие в этом Redis; здесь оставляем только свой.
     * Битый сэмпл (array_combine keys/values) не должен валить весь /metrics 500-кой.
     */
    public function collect(): array
    {
        $prefix = $this->config->namespace . '_';

        try {
            $families = $this->adapter->collect();
        } catch (\Throwable) {
            return [];
        }

        return array_values(array_filter(
            $families,
            static fn (MetricFamilySamples $family): bool => str_starts_with($family->getName(), $prefix),
        ));
    }

    public function createCounter(
        string $name,
        string $help = '',
        array $labels = [],
    ): Counter {
        return new Counter(
            storageAdapter: $this->adapter,
            namespace: $this->config->namespace,
            name: $name,
            help: $help,
            labels: $labels,
        );
    }

    public function createGauge(
        string $name,
        string $help = '',
        array $labels = [],
    ): Gauge {
        return new Gauge(
            storageAdapter: $this->adapter,
            namespace: $this->config->namespace,
            name: $name,
            help: $help,
            labels: $labels,
        );
    }

    public function createHistogram(
        string $name,
        string $help = '',
        array $labels = [],
        ?array $buckets = null,
    ): Histogram {
        return new Histogram(
            adapter: $this->adapter,
            namespace: $this->config->namespace,
            name: $name,
            help: $help,
            labels: $labels,
            buckets: $buckets,
        );
    }
}
