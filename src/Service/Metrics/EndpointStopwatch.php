<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Service\Metrics;

use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Секундомер только для длительности endpoint. Не debug.stopwatch профайлера:
 * тот в prod не зарегистрирован, а общий экземпляр нельзя сбрасывать.
 */
final class EndpointStopwatch
{
    private readonly Stopwatch $stopwatch;

    private int $sequence = 0;

    /**
     * @var array<string, string>
     */
    private array $sections = [];

    public function __construct()
    {
        $this->stopwatch = new Stopwatch(morePrecision: true);
    }

    public function start(string $key): void
    {
        $section = $key . '#' . ++$this->sequence;
        $this->stopwatch->start($section);
        $this->sections[$key] = $section;
    }

    public function stop(string $key): ?float
    {
        $section = $this->sections[$key] ?? null;
        if (null === $section) {
            return null;
        }

        unset($this->sections[$key]);
        if (!$this->stopwatch->isStarted($section)) {
            return null;
        }

        return $this->stopwatch->stop($section)->getDuration() / 1000;
    }

    public function reset(): void
    {
        $this->sections = [];
        $this->sequence = 0;
        $this->stopwatch->reset();
    }
}
