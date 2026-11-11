<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Entry\Console\StaleConsoleCommands;

use IWD\SymfonyEntryContract\Interfaces\InputContractInterface;
use Symfony\Component\Validator\Constraints\Positive;

class InputContract implements InputContractInterface
{
    /** Сколько команд показать */
    #[Positive]
    public int $limit = 20;
}
