<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Entry\Console\TestLog;

use Exception;
use IWD\SymfonyEntryContract\Attribute\CliContract;
use IWD\SymfonyAppCommon\Container\AppCliCommand;
use IWD\SymfonyEntryContract\Interfaces\InputContractInterface;
use IWD\SymfonyEntryContract\Service\CliContractResolver;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:common:test-log',
    description: 'Отправка тестового лога'
)]
#[CliContract(class: InputContract::class)]
class TestLogCommand extends AppCliCommand
{
    public function __construct(
        CliContractResolver $cliContractResolver,
        LoggerInterface $logger,
    ) {
        parent::__construct(cliContractResolver: $cliContractResolver, logger: $logger);
    }

    /**
     * @throws Exception
     */
    protected function handle(InputContractInterface $inputContract): int
    {
        $this->logger->info('I just got the logger');
        $this->logger->error('An error occurred');

        // log messages can also contain placeholders, which are variable names
        // wrapped in braces whose values are passed as the second argument
        $this->logger->debug('User {userId} has logged in', [
            'userId' => 123,
        ]);

        $this->logger->critical('I left the oven on!', [
            // include extra "context" info in your logs
            'cause' => 'in_hurry',
        ]);

        $this->logger->info('My log without tags');
        $this->logger->info('My log with tags', [
            'tags' => ['foo', 'bar', 'baz'],
            'stamps' => [
                'APP_BUILD_TAG' => 'auto-255',
            ],
        ]);

        throw new Exception('My exception');
        // return self::SUCCESS;
    }
}
