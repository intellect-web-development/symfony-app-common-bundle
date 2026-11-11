<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Service\Redis;

use Redis;

class RedisConnection
{
    private readonly Redis $redis;
    private bool $isConnection = false;

    public function __construct(
        public readonly Config $config
    ) {
        $this->redis = new Redis();
    }

    public function connect(): bool
    {
        if (!$this->isConnection) {
            $this->isConnection = $this->redis->connect(
                $this->config->host,
                (int) $this->config->port,
            );
            $this->redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_JSON);
        }

        return $this->isConnection;
    }

    public function getRedis(): Redis
    {
        return $this->redis;
    }
}
