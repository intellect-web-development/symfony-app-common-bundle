<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Infrastructure\Doctrine;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Query\QueryBuilder;

final class QueryBuilderParametersCopier
{
    public static function copy(QueryBuilder $source, QueryBuilder $target): void
    {
        $types = $source->getParameterTypes();

        foreach ($source->getParameters() as $key => $value) {
            self::setParameter(target: $target, key: (string) $key, value: $value, type: $types[$key] ?? null);
        }
    }

    public static function copyMissing(QueryBuilder $source, QueryBuilder $target): void
    {
        $existing = $target->getParameters();
        $types = $source->getParameterTypes();

        foreach ($source->getParameters() as $key => $value) {
            if (\array_key_exists($key, $existing)) {
                continue;
            }

            self::setParameter(target: $target, key: (string) $key, value: $value, type: $types[$key] ?? null);
        }
    }

    private static function setParameter(QueryBuilder $target, string $key, mixed $value, mixed $type): void
    {
        if (null !== $type) {
            $target->setParameter($key, $value, $type);

            return;
        }

        if (\is_array($value)) {
            $target->setParameter($key, $value, ArrayParameterType::STRING);

            return;
        }

        $target->setParameter($key, $value);
    }
}
