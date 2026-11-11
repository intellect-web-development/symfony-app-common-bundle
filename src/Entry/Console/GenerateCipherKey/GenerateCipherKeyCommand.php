<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Entry\Console\GenerateCipherKey;

use IWD\SymfonyAppCommon\Service\Cipher\CipherConfig;
use IWD\SymfonyEntryContract\Attribute\CliContract;
use IWD\SymfonyAppCommon\Container\AppCliCommand;
use IWD\SymfonyEntryContract\Interfaces\InputContractInterface;
use IWD\SymfonyEntryContract\Service\CliContractResolver;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\String\ByteString;

#[AsCommand(
    name: 'app:common:cipher:generate-key',
    description: 'Генерация пары key, vector для шифрования'
)]
#[CliContract(class: InputContract::class)]
class GenerateCipherKeyCommand extends AppCliCommand
{
    public function __construct(
        CliContractResolver $cliContractResolver,
        LoggerInterface $logger,
    ) {
        parent::__construct(cliContractResolver: $cliContractResolver, logger: $logger);
    }

    protected function handle(InputContractInterface $inputContract): int
    {
        $key = ByteString::fromRandom(CipherConfig::AES_KEY_LENGTH)->toString();
        $vector = ByteString::fromRandom(CipherConfig::AES_KEY_LENGTH)->toString();

        $this->io->writeln("CIPHER_KEY=\"{$key}\"");
        $this->io->writeln("CIPHER_VECTOR=\"{$vector}\"");

        return self::SUCCESS;
    }
}
