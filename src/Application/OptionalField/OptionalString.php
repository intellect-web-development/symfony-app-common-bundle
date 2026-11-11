<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Application\OptionalField;

final class OptionalString
{
    /**
     * null = не передали (сохранить текущее); '' = очистить; иначе trim.
     */
    public static function merge(?string $incoming, ?string $current): ?string
    {
        if (null === $incoming) {
            return $current;
        }

        $trimmed = trim($incoming);

        return '' === $trimmed ? null : $trimmed;
    }
}
