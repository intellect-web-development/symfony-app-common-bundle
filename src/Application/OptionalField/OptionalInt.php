<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Application\OptionalField;

final class OptionalInt
{
    /**
     * null = не передали (сохранить текущее); '' / whitespace = очистить; иначе int.
     */
    public static function merge(?string $incoming, ?int $current): ?int
    {
        if (null === $incoming) {
            return $current;
        }

        return self::parseNullable($incoming);
    }

    /**
     * null / '' / whitespace → null; иначе int.
     */
    public static function parseNullable(?string $incoming): ?int
    {
        if (null === $incoming) {
            return null;
        }

        $trimmed = trim($incoming);

        return '' === $trimmed ? null : (int) $trimmed;
    }
}
