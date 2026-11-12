<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Service\Metrics\Adapters\Redis;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class Config
{
    public string $namespace;

    public function __construct(
        #[Autowire('%env(METRICS_SIDECAR_HOST)%')]
        public string $host,
        #[Autowire('%env(METRICS_NAMESPACE)%')]
        string $namespace,
    ) {
        // Prometheus metric names: [a-zA-Z_:][a-zA-Z0-9_:]* — hyphens (e.g. from APP_SLUG) are invalid.
        $this->namespace = str_replace('-', '_', $namespace);
    }

    public function redisPrefix(): string
    {
        return 'PROMETHEUS_' . $this->namespace . '_';
    }
}
