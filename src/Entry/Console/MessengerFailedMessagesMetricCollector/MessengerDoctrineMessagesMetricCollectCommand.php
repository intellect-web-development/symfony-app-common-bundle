<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Entry\Console\MessengerFailedMessagesMetricCollector;

use IWD\SymfonyAppCommon\Service\Metrics\AdapterInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use IWD\SymfonyEntryContract\Service\CliContractResolver;
use IWD\SymfonyEntryContract\Attribute\CliContract;
use IWD\SymfonyAppCommon\Container\AppCliCommand;
use IWD\SymfonyEntryContract\Interfaces\InputContractInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:common:messenger-doctrine-messages-collect',
    description: 'Collect doctrine messages in queue',
)]
#[CliContract(class: InputContract::class)]
class MessengerDoctrineMessagesMetricCollectCommand extends AppCliCommand
{
    public function __construct(
        CliContractResolver $cliContractResolver,
        LoggerInterface $logger,
        private readonly Connection $connection,
        private readonly AdapterInterface $adapter,
    ) {
        parent::__construct(cliContractResolver: $cliContractResolver, logger: $logger);
    }

    /**
     * @return int
     * @throws Exception
     */
    protected function handle(InputContractInterface $inputContract): int
    {
        assert($inputContract instanceof InputContract);
        $queuesAndCounts = $this->connection->prepare(
            <<<SQL
                WITH queue_names AS (
                    SELECT DISTINCT queue_name
                    FROM {$inputContract->messengerTable}
                ),
                     queue_counts AS (SELECT queue_name, COUNT(*) AS count FROM {$inputContract->messengerTable} GROUP BY queue_name)
                SELECT
                    queue_names.queue_name,
                    COALESCE(queue_counts.count, 0) AS message_count
                FROM
                    queue_names LEFT JOIN queue_counts ON queue_names.queue_name = queue_counts.queue_name
                SQL
        )->executeQuery()->fetchAllAssociative();

        $gauge = $this->adapter->createGauge(
            name: 'actual_messages_in_doctrine_queue',
            help: 'Actual messages in doctrine queue',
            labels: [
                'queue',
            ]
        );
        $seenFailed = false;
        foreach ($queuesAndCounts as $queuesAndCount) {
            $queueName = (string) $queuesAndCount['queue_name'];
            $gauge->set((float) $queuesAndCount['message_count'], [
                $queueName,
            ]);

            $this->io->success($queueName . ': ' . $queuesAndCount['message_count']);
            if ('failed' === $queueName) {
                $seenFailed = true;
            }
        }
        if (!$seenFailed) {
            $gauge->set(0, ['failed']);
            $this->io->success('failed: 0');
        }

        return self::SUCCESS;
    }
}
