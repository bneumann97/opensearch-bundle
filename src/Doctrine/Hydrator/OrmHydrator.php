<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Doctrine\Hydrator;

use Bneumann\OpensearchBundle\Finder\HydratorInterface;
use Bneumann\OpensearchBundle\Index\IndexDefinition;
use Doctrine\Persistence\ManagerRegistry;

final class OrmHydrator implements HydratorInterface
{
    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly string $modelClass,
        private readonly string $identifier,
    ) {
    }

    public function hydrate(IndexDefinition $index, array $hits): array
    {
        $ids = [];
        foreach ($hits as $hit) {
            if (isset($hit['_id'])) {
                $ids[] = $hit['_id'];
            }
        }

        if (empty($ids)) {
            return [];
        }

        $repository = $this->registry->getRepository($this->modelClass);
        $entities = $repository->findBy([$this->identifier => $ids]);

        $byId = [];
        foreach ($entities as $entity) {
            $id = $this->readIdentifier($entity);
            if ($id !== null) {
                $byId[(string) $id] = $entity;
            }
        }

        $ordered = [];
        foreach ($ids as $id) {
            if (isset($byId[(string) $id])) {
                $ordered[] = $byId[(string) $id];
            }
        }

        return $ordered;
    }

    private function readIdentifier(object $entity): mixed
    {
        $getter = 'get' . ucfirst($this->identifier);
        if (method_exists($entity, $getter)) {
            return $entity->{$getter}();
        }

        if (property_exists($entity, $this->identifier)) {
            return $entity->{$this->identifier};
        }

        return null;
    }
}
