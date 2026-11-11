<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\EventSubscriber\Metrics;

use IWD\SymfonyAppCommon\Dictionary\LogTag;
use IWD\SymfonyAppCommon\Service\Metrics\AdapterInterface;
use IWD\SymfonyAppCommon\Service\Metrics\ConsoleCommandUsageRanker;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Throwable;

#[AsEventListener(event: ConsoleEvents::COMMAND, method: 'onSendMetrics', priority: 1)]
readonly class ConsoleCallSubscriber
{
    public function __construct(
        private AdapterInterface $adapter,
        private LoggerInterface $logger,
    ) {
    }

    public function onSendMetrics(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();
        if (null !== $command && !in_array($command->getName(), self::getIgnoreCommandList(), true)) {
            $this->recordUsage((string) $command->getName());
        }

        if (null !== $command) {
            $this->logger->info($command->getName() ?? '', [
                'description' => $command->getDescription(),
                'tag' => LogTag::RUN_COMMAND,
            ]);
        }
    }

    /**
     * @return list<string>
     */
    public static function getIgnoreCommandList(): array
    {
        return [
            'cache:clear',
            'assets:install',
            'fos:js-routing:dump',
        ];
    }

    private function recordUsage(string $commandName): void
    {
        try {
            $this->adapter->createCounter(
                name: ConsoleCommandUsageRanker::CALLS_METRIC,
                help: 'Console calls counter',
                labels: [ConsoleCommandUsageRanker::COMMAND_LABEL],
            )->inc([$commandName]);

            $this->adapter->createGauge(
                name: ConsoleCommandUsageRanker::LAST_USED_METRIC,
                help: 'Unix time of the last console command start',
                labels: [ConsoleCommandUsageRanker::COMMAND_LABEL],
            )->set((float) time(), [$commandName]);
        } catch (Throwable $exception) {
            $this->logger->error('Failed to record console command metrics', [
                'command' => $commandName,
                'exception' => $exception,
            ]);
        }
    }
}
