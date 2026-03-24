<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Doctrine\Listener;

use Bneumann\OpensearchBundle\Index\IndexDefinition;
use Bneumann\OpensearchBundle\Persister\PersisterInterface;

final class OrmIndexConfig
{
    public function __construct(
        private readonly IndexDefinition $index,
        private readonly PersisterInterface $persister,
        private readonly string $modelClass,
        private readonly string $identifier,
        private readonly ?string $indexable,
    ) {
    }

    public function getIndex(): IndexDefinition
    {
        return $this->index;
    }

    public function getPersister(): PersisterInterface
    {
        return $this->persister;
    }

    public function getModelClass(): string
    {
        return $this->modelClass;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getIndexable(): ?string
    {
        return $this->indexable;
    }
}
