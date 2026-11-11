<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Infrastructure\Doctrine\Timestamp;

use IWD\SymfonyAppCommon\Domain\Attribute\UpdatedAt;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Psr\Clock\ClockInterface;
use ReflectionClass;

/**
 * Перед UPDATE выставляет «сейчас» всем свойствам сущности, помеченным #[UpdatedAt].
 * UnitOfWork пересчитывает changeSet после слушателей, поэтому значение попадает в запрос.
 */
#[AsDoctrineListener(event: Events::preUpdate)]
final class UpdatedAtListener
{
    /** @var array<class-string, list<string>> */
    private array $properties = [];

    public function __construct(private readonly ClockInterface $clock)
    {
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        $metadata = $args->getObjectManager()->getClassMetadata($entity::class);
        $now = $this->clock->now();

        foreach ($this->propertiesOf($entity::class) as $property) {
            $metadata->setFieldValue($entity, $property, $now);
        }
    }

    /**
     * @param class-string $class
     *
     * @return list<string>
     */
    private function propertiesOf(string $class): array
    {
        if (!isset($this->properties[$class])) {
            $this->properties[$class] = [];
            foreach ((new ReflectionClass($class))->getProperties() as $property) {
                if ([] !== $property->getAttributes(UpdatedAt::class)) {
                    $this->properties[$class][] = $property->getName();
                }
            }
        }

        return $this->properties[$class];
    }
}
