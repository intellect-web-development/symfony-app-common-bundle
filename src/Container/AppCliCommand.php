<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Container;

use IWD\SymfonyEntryContract\Console\CliCommand;
use IWD\SymfonyEntryContract\Service\CliContractResolver;
use Psr\Log\LoggerInterface;

abstract class AppCliCommand extends CliCommand
{
    public function __construct(
        private readonly CliContractResolver $cliContractResolver,
        protected readonly LoggerInterface $logger,
    ) {
        parent::__construct($this->cliContractResolver);
    }

    protected function writeWarn(string $message): void
    {
        $this->io->warning($message);
        $this->logger->warning($message);
    }

    protected function writeSuccess(string $message): void
    {
        $this->io->success($message);
        $this->logger->info($message);
    }
}
