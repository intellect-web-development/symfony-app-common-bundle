<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Service\Metrics;

use IWD\SymfonyAppCommon\Exception\Domain\DomainException;
use SensitiveParameter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class MetricsGuard
{
    public function __construct(
        #[Autowire('%env(PROMETHEUS_TOKEN)%')]
        private string $prometheusToken,
    ) {
    }

    public function guard(#[SensitiveParameter] string $token): void
    {
        if ($token !== $this->prometheusToken) {
            throw new DomainException('Invalid permissions');
        }
    }
}
