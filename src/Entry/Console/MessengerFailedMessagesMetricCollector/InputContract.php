<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Entry\Console\MessengerFailedMessagesMetricCollector;

use IWD\SymfonyEntryContract\Interfaces\InputContractInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class InputContract implements InputContractInterface
{
    /** Название таблицы, где хранится очередь сообщений */
    #[NotNull]
    #[NotBlank]
    #[Length(min: 1)]
    public string $messengerTable = 'messenger_messages';
}
