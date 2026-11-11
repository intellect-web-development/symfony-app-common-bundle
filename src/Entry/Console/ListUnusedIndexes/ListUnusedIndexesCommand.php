<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Entry\Console\ListUnusedIndexes;

use IWD\SymfonyAppCommon\Container\AppCliCommand;
use DateTimeInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use IWD\SymfonyEntryContract\Attribute\CliContract;
use IWD\SymfonyEntryContract\Interfaces\InputContractInterface;
use IWD\SymfonyEntryContract\Service\CliContractResolver;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:common:list-unused-indexes',
    description: 'Показать индексы PostgreSQL без сканирований (кандидаты на удаление; PK/UNIQUE исключены). Статистика per-instance — реплики проверяйте отдельно.',
)]
#[CliContract(class: InputContract::class)]
class ListUnusedIndexesCommand extends AppCliCommand
{
    public function __construct(
        CliContractResolver $cliContractResolver,
        LoggerInterface $logger,
        private readonly Connection $connection,
    ) {
        parent::__construct(cliContractResolver: $cliContractResolver, logger: $logger);
    }

    /**
     * @param  InputContract $inputContract
     * @throws Exception
     */
    protected function handle(InputContractInterface $inputContract): int
    {
        $statsReset = $this->connection->fetchOne(
            'SELECT stats_reset FROM pg_stat_database WHERE datname = current_database()'
        );
        $this->io->warning(sprintf(
            'Окно статистики idx_scan: stats_reset = %s. Короткое окно делает список ненадёжным. Статистика per-instance — на репликах проверьте отдельно.',
            $this->formatStatsReset($statsReset),
        ));

        $rows = $this->connection->fetchAllAssociative(
            <<<SQL
                SELECT
                    s.schemaname AS schema,
                    s.relname AS table_name,
                    s.indexrelname AS index_name,
                    s.idx_scan AS index_scans,
                    pg_size_pretty(pg_relation_size(s.indexrelid)) AS index_size
                FROM pg_catalog.pg_stat_user_indexes s
                JOIN pg_catalog.pg_index i ON s.indexrelid = i.indexrelid
                WHERE s.idx_scan = 0
                  AND NOT i.indisunique
                  AND NOT EXISTS (
                      SELECT 1
                      FROM pg_catalog.pg_constraint c
                      WHERE c.conindid = s.indexrelid
                  )
                ORDER BY pg_relation_size(s.indexrelid) DESC
                SQL
        );

        if ([] === $rows) {
            $this->io->success('Нет кандидатов: индексов с idx_scan = 0 (кроме PK/UNIQUE/constraint) не найдено.');

            return self::SUCCESS;
        }

        $this->io->table(
            ['schema', 'table', 'index', 'scans', 'size'],
            array_map(
                static fn (array $row): array => [
                    (string) $row['schema'],
                    (string) $row['table_name'],
                    (string) $row['index_name'],
                    (string) $row['index_scans'],
                    (string) $row['index_size'],
                ],
                $rows,
            ),
        );
        $this->io->success(sprintf('Кандидатов: %d. Команда ничего не удаляет.', count($rows)));

        return self::SUCCESS;
    }

    private function formatStatsReset(mixed $statsReset): string
    {
        if ($statsReset instanceof DateTimeInterface) {
            return $statsReset->format('Y-m-d H:i:s P');
        }

        if (is_string($statsReset) && '' !== $statsReset) {
            return $statsReset;
        }

        return 'неизвестно (с момента старта инстанса или stats ещё не сбрасывались)';
    }
}
