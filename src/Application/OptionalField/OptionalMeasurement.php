<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Application\OptionalField;

use IWD\Measurement\Core\Math\ValueObject\Measurement;

final class OptionalMeasurement
{
    /**
     * null = не передали (сохранить текущее); '' / whitespace = очистить; иначе Measurement.
     */
    public static function merge(?string $incoming, ?Measurement $current): ?Measurement
    {
        if (null === $incoming) {
            return $current;
        }

        return self::parseNullable($incoming);
    }

    /**
     * null / '' / whitespace → null; иначе Measurement.
     */
    public static function parseNullable(?string $incoming): ?Measurement
    {
        if (null === $incoming) {
            return null;
        }

        $trimmed = trim($incoming);

        return '' === $trimmed ? null : new Measurement($trimmed);
    }
}
