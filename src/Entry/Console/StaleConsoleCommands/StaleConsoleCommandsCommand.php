<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Entry\Console\StaleConsoleCommands;

use IWD\SymfonyAppCommon\Container\AppCliCommand;
use IWD\SymfonyAppCommon\Service\Metrics\AdapterInterface;
use IWD\SymfonyAppCommon\Service\Metrics\ConsoleCommandDescriptor;
use IWD\SymfonyAppCommon\Service\Metrics\ConsoleCommandUsage;
use IWD\SymfonyAppCommon\Service\Metrics\ConsoleCommandUsageRanker;
use DateTimeImmutable;
use DateTimeZone;
use IWD\SymfonyEntryContract\Attribute\CliContract;
use IWD\SymfonyEntryContract\Interfaces\InputContractInterface;
use IWD\SymfonyEntryContract\Service\CliContractResolver;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\LazyCommand;
use Throwable;

#[AsCommand(
    name: 'app:common:console-audit:stale',
    description: 'Показать команды приложения, которые не запускались или запускались давно и редко.',
)]
#[CliContract(class: InputContract::class)]
class StaleConsoleCommandsCommand extends AppCliCommand
{
    public function __construct(
        CliContractResolver $cliContractResolver,
        LoggerInterface $logger,
        private readonly AdapterInterface $metrics,
        private readonly ConsoleCommandUsageRanker $ranker,
    ) {
        parent::__construct(cliContractResolver: $cliContractResolver, logger: $logger);
    }

    /**
     * @param InputContract $inputContract
     */
    protected function handle(InputContractInterface $inputContract): int
    {
        $application = $this->getApplication();
        if (!$application instanceof Application) {
            $this->io->error('Консольное приложение не задано.');

            return self::FAILURE;
        }

        try {
            $families = $this->metrics->collect();
        } catch (Throwable $exception) {
            $this->writeWarn('Не удалось прочитать метрики: ' . $exception->getMessage());

            return self::FAILURE;
        }

        $commands = $this->applicationCommands($application);
        $ranked = $this->ranker->rank(commands: $commands, families: $families, limit: $inputContract->limit);

        if ([] === $ranked) {
            $this->io->success('Команд приложения для отчёта нет.');

            return self::SUCCESS;
        }

        $this->io->table(
            ['command', 'description', 'last used', 'calls'],
            array_map(
                fn (ConsoleCommandUsage $usage): array => [
                    $usage->name,
                    $usage->description,
                    $this->formatLastUsed($usage),
                    null === $usage->callCount ? '—' : (string) $usage->callCount,
                ],
                $ranked,
            ),
        );
        $this->io->success(sprintf(
            'Показано %d из %d. Накопленное число — с момента жизни серии в Redis, не за выбранный период. Команда ничего не удаляет.',
            count($ranked),
            count($commands),
        ));

        return self::SUCCESS;
    }

    /**
     * @return list<ConsoleCommandDescriptor>
     */
    private function applicationCommands(Application $application): array
    {
        $commands = [];
        foreach ($application->all() as $command) {
            $name = $command->getName();
            if (!is_string($name) || '' === $name || isset($commands[$name])) {
                continue;
            }

            try {
                $resolved = $command instanceof LazyCommand ? $command->getCommand() : $command;
            } catch (Throwable $exception) {
                $this->writeWarn(sprintf('Не удалось загрузить команду %s: %s', $name, $exception->getMessage()));
                continue;
            }

            if (!str_starts_with($resolved::class, 'App\\')) {
                continue;
            }

            $commands[$name] = new ConsoleCommandDescriptor(name: $name, description: $resolved->getDescription());
        }

        return array_values($commands);
    }

    private function formatLastUsed(ConsoleCommandUsage $usage): string
    {
        if ($usage->wasNeverUsed()) {
            return 'не запускалась';
        }

        if (null === $usage->lastUsedAt) {
            return 'неизвестно';
        }

        return (new DateTimeImmutable('@' . $usage->lastUsedAt))
            ->setTimezone(new DateTimeZone(date_default_timezone_get()))
            ->format('Y-m-d H:i:s P');
    }
}
