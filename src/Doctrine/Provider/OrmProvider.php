<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Doctrine\Provider;

use Bneumann\OpensearchBundle\Index\IndexDefinition;
use Bneumann\OpensearchBundle\Provider\ProviderInterface;
use Doctrine\Persistence\ManagerRegistry;

final class OrmProvider implements ProviderInterface
{
    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly string $modelClass,
    ) {
    }

    public function provide(IndexDefinition $index, int $batchSize): iterable
    {
        $em = $this->registry->getManagerForClass($this->modelClass);
        $repository = $em->getRepository($this->modelClass);

        $queryBuilder = $repository->createQueryBuilder('e');
        $query = $queryBuilder->getQuery();

        $iterable = method_exists($query, 'toIterable') ? $query->toIterable() : $query->iterate();

        foreach ($iterable as $row) {
            if (is_array($row)) {
                $row = reset($row);
            }

            yield $row;
        }
    }
}
