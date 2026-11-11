<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Service\Metrics;

final readonly class EndpointDurationRecorder
{
    public function __construct(
        private AdapterInterface $adapter,
    ) {
    }

    public function record(string $name, string $kind, int $statusCode, float $seconds): void
    {
        $this->adapter->createHistogram(
            name: EndpointDuration::NAME,
            help: EndpointDuration::HELP,
            labels: EndpointDuration::LABELS,
            buckets: EndpointDuration::BUCKETS,
        )->observe(max(0.0, $seconds), [
            $name,
            $kind,
            EndpointDuration::statusClass($statusCode),
        ]);
    }
}
