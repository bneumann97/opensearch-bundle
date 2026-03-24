<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Doctrine\Listener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

final class OrmIndexListener implements EventSubscriber
{
    /** @var array<int, array{config: OrmIndexConfig, entity: object}> */
    private array $scheduledIndex = [];

    /** @var array<int, array{config: OrmIndexConfig, entity: object}> */
    private array $scheduledDelete = [];

    /** @param OrmIndexConfig[] $configs */
    public function __construct(private readonly array $configs, private readonly IndexableChecker $checker)
    {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postUpdate,
            Events::postRemove,
            Events::postFlush,
        ];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $this->schedule($args->getObject(), $this->scheduledIndex);
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->schedule($args->getObject(), $this->scheduledIndex);
    }

    public function postRemove(LifecycleEventArgs $args): void
    {
        $this->schedule($args->getObject(), $this->scheduledDelete);
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        $toIndex = $this->scheduledIndex;
        $toDelete = $this->scheduledDelete;
        $this->scheduledIndex = [];
        $this->scheduledDelete = [];

        foreach ($toIndex as $item) {
            $this->indexEntity($item['config'], $item['entity']);
        }

        foreach ($toDelete as $item) {
            $this->deleteEntity($item['config'], $item['entity']);
        }
    }

    private function schedule(object $entity, array &$queue): void
    {
        foreach ($this->configs as $config) {
            if (!is_a($entity, $config->getModelClass())) {
                continue;
            }

            if (!$this->checker->isIndexable($entity, $config->getIndexable())) {
                continue;
            }

            $queue[] = ['config' => $config, 'entity' => $entity];
        }
    }

    private function indexEntity(OrmIndexConfig $config, object $entity): void
    {
        $id = $this->readIdentifier($entity, $config->getIdentifier());
        $config->getPersister()->insertOne($config->getIndex(), $entity, $id === null ? null : (string) $id);
    }

    private function deleteEntity(OrmIndexConfig $config, object $entity): void
    {
        $id = $this->readIdentifier($entity, $config->getIdentifier());
        if ($id === null) {
            return;
        }

        $config->getPersister()->deleteOne($config->getIndex(), (string) $id);
    }

    private function readIdentifier(object $entity, string $identifier): mixed
    {
        $getter = 'get' . ucfirst($identifier);
        if (method_exists($entity, $getter)) {
            return $entity->{$getter}();
        }

        if (property_exists($entity, $identifier)) {
            return $entity->{$identifier};
        }

        return null;
    }
}
