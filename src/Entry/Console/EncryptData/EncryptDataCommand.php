<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Entry\Console\EncryptData;

use IWD\SymfonyAppCommon\Service\Cipher\AesCipher;
use IWD\SymfonyAppCommon\Service\Cipher\SystemCipherKey;
use IWD\SymfonyEntryContract\Attribute\CliContract;
use IWD\SymfonyAppCommon\Container\AppCliCommand;
use IWD\SymfonyEntryContract\Interfaces\InputContractInterface;
use IWD\SymfonyEntryContract\Service\CliContractResolver;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:common:cipher:encrypt-data',
    description: 'Тестовое шифрование данных'
)]
#[CliContract(class: InputContract::class)]
class EncryptDataCommand extends AppCliCommand
{
    private readonly AesCipher $cipher;

    public function __construct(
        SystemCipherKey $systemCipherKey,
        CliContractResolver $cliContractResolver,
        LoggerInterface $logger,
    ) {
        parent::__construct(cliContractResolver: $cliContractResolver, logger: $logger);
        $this->cipher = new AesCipher($systemCipherKey);
    }

    /**
     * @param InputContract $inputContract
     */
    protected function handle(InputContractInterface $inputContract): int
    {
        $encryptedData = $this->cipher->encrypt($inputContract->data);
        $decryptedData = $this->cipher->decrypt($encryptedData);

        $this->output->writeln("encryptedData - {$encryptedData}");
        $this->output->writeln("decryptedData - {$decryptedData}");

        return self::SUCCESS;
    }
}
