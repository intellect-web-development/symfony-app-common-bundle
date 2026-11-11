<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Doctrine\Type;

use Doctrine\DBAL\Types\Type;
use IWD\Measurement\Core\Math\ValueObject\Measurement;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class MeasurementType extends Type
{
    public const string NAME = 'common_measurement';

    /**
     * {@inheritDoc}
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getDecimalTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?float
    {
        return $value instanceof Measurement ? (float) $value->getValue() : $value;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?Measurement
    {
        return is_numeric($value) ? new Measurement((string) $value) : null;
    }
}
