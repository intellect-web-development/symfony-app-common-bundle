<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Exception\Domain;

use Throwable;

class DomainException extends \DomainException
{
    public function __construct(
        string $message = '',
        ?Throwable $previous = null,
        public readonly ?array $context = null,
    ) {
        parent::__construct(
            message: $message,
            previous: $previous,
        );
    }
}
