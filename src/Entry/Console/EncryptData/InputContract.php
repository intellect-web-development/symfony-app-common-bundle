<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Entry\Console\EncryptData;

use IWD\SymfonyEntryContract\Interfaces\InputContractInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class InputContract implements InputContractInterface
{
    #[NotNull]
    #[NotBlank]
    public string $data;
}
