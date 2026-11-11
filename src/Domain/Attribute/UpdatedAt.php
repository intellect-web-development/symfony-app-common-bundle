<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Domain\Attribute;

use Attribute;

/**
 * Свойство DateTimeImmutable, которое Doctrine обновляет текущим временем при каждом UPDATE сущности.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class UpdatedAt
{
}
