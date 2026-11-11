<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Service\Lock;

use Redis;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Lock\Store\RedisStore;

readonly class LockService
{
    private RedisStore $store;
    private LockFactory $lockFactory;

    public function __construct(
        #[Autowire('%env(resolve:REDIS_HOST)%')]
        private string $redisHost,
        #[Autowire('%env(resolve:REDIS_PORT)%')]
        private int $redisPort,
        #[Autowire('%env(resolve:REDIS_PASSWORD)%')]
        private string $redisPassword,
    ) {
        $redis = new Redis();

        if ('none' !== $this->redisPassword) {
            $redis->auth($this->redisPassword);
        }

        $redis->connect(host: $this->redisHost, port: $this->redisPort);
        $this->store = new RedisStore($redis);
        $this->lockFactory = new LockFactory($this->store);
    }

    public function createLock(string $key, ?int $ttl = 10, bool $autoRelease = true): LockInterface
    {
        return $this->lockFactory->createLock($key, $ttl, $autoRelease);
    }
}
