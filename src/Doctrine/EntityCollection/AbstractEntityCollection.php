<?php

declare(strict_types=1);

namespace IWD\SymfonyAppCommon\Doctrine\EntityCollection;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Generator;

abstract readonly class AbstractEntityCollection
{
    public function __construct(
        protected EntityManagerInterface $em,
    ) {
    }

    abstract protected function getQueryBuilder(): QueryBuilder;

    public function iterate(
        int $size,
        int $offset = 0,
    ): Generator {
        return $this->iterateProcess(size: $size, offset: $offset);
    }

    protected function getTotalCount(): int
    {
        $countQueryBuilder = clone $this->getQueryBuilder();

        return (int) $countQueryBuilder
            ->resetDQLPart('orderBy')
            ->resetDQLPart('groupBy')
            ->select('count(1)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    protected function iterateProcess(
        int $size,
        int $offset,
    ): Generator {
        $count = $this->getTotalCount();

        while ($offset < $count) {
            $entities = $this->getQueryBuilder()
                ->setFirstResult($offset)
                ->setMaxResults($size)
                ->getQuery()
                ->getResult()
            ;
            foreach ($entities as $entity) {
                yield $entity;
            }

            $offset += $size;
            $this->em->clear();
        }
    }
}
