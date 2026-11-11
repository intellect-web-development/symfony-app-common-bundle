<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Service\Metrics;

final readonly class ConsoleCommandDescriptor
{
    public function __construct(
        public string $name,
        public string $description,
    ) {
    }
}
