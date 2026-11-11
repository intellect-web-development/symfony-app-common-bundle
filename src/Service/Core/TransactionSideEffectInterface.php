<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Service\Core;

interface TransactionSideEffectInterface
{
    public function enterTransaction(): void;

    public function leaveTransaction(bool $committed): void;
}
