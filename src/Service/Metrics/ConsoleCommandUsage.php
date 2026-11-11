<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Service\Metrics;

final readonly class ConsoleCommandUsage
{
    public function __construct(
        public string $name,
        public string $description,
        public ?int $callCount,
        public ?int $lastUsedAt,
    ) {
    }

    public function wasNeverUsed(): bool
    {
        return null === $this->callCount && null === $this->lastUsedAt;
    }
}
