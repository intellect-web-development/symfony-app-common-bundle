<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Service\Json;

use JsonException;

final class JsonDecoder
{
    private const int JSON_DECODE_MAX_DEPTH = 2147483647;

    /**
     * @return array<mixed>
     *
     * @throws JsonException
     */
    public static function decodeAssociative(string $json, int $depth = 512): array
    {
        $depth = max(1, min($depth, self::JSON_DECODE_MAX_DEPTH));

        if (!json_validate($json, $depth)) {
            throw new JsonException('Invalid JSON payload');
        }

        /** @var array<mixed> $decoded */
        $decoded = json_decode($json, true, $depth, JSON_THROW_ON_ERROR);

        return $decoded;
    }
}
