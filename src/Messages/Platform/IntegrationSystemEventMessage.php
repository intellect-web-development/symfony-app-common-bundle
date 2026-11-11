<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Messages\Platform;

readonly class IntegrationSystemEventMessage
{
    /**
     * @param array<string, string> $payload
     */
    public function __construct(
        public string $type,
        public string $integrationSystemCode,
        public array $payload,
    ) {
    }
}
