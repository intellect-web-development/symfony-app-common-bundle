<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Service\Core;

use Doctrine\ORM\EntityManagerInterface;
use Throwable;

readonly class Flusher
{
    public function __construct(
        private EntityManagerInterface $em,
        private ?TransactionSideEffectInterface $transactionSideEffect = null,
    ) {
    }

    public function flush(): void
    {
        $this->em->flush();
    }

    /**
     * @template T
     *
     * @param callable(): T $work
     *
     * @return T
     */
    public function transactional(callable $work): mixed
    {
        if (null === $this->transactionSideEffect) {
            return $this->em->wrapInTransaction($work);
        }

        $this->transactionSideEffect->enterTransaction();
        try {
            $result = $this->em->wrapInTransaction($work);
        } catch (Throwable $exception) {
            $this->transactionSideEffect->leaveTransaction(committed: false);

            throw $exception;
        }
        $this->transactionSideEffect->leaveTransaction(committed: true);

        return $result;
    }
}
