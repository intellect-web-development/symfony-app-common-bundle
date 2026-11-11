<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Service\Redis;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class Config
{
    public function __construct(
        #[Autowire('%env(REDIS_HOST)%')]
        public string $host,
        #[Autowire('%env(REDIS_PORT)%')]
        public string $port,
    ) {
    }
}
