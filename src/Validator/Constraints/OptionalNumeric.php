<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Validator\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * Допускает null / пустую (или whitespace) строку — сигнал очистки — или numeric-значение.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class OptionalNumeric extends Constraint
{
    public const string NOT_NUMERIC_ERROR = 'a3f0c8e1-7b2d-4e9a-9c1f-5d6e8a0b1c2d';

    protected const ERROR_NAMES = [
        self::NOT_NUMERIC_ERROR => 'NOT_NUMERIC_ERROR',
    ];

    public string $message = 'This value should be of type {{ type }}.';

    public function __construct(?string $message = null, ?array $groups = null, mixed $payload = null)
    {
        parent::__construct(null, $groups, $payload);

        $this->message = $message ?? $this->message;
    }
}
