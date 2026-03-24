<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Tests\Unit\Doctrine;

use Bneumann\OpensearchBundle\Doctrine\Listener\IndexableChecker;
use Bneumann\OpensearchBundle\Doctrine\Listener\OrmIndexConfig;
use Bneumann\OpensearchBundle\Doctrine\Listener\OrmIndexListener;
use Bneumann\OpensearchBundle\Index\IndexDefinition;
use Bneumann\OpensearchBundle\Persister\PersisterInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class OrmIndexListenerTest extends TestCase
{
    private PersisterInterface&MockObject $persister;
    private IndexableChecker $checker;
    private IndexDefinition $index;
    private OrmIndexConfig $config;

    protected function setUp(): void
    {
        $this->persister = $this->createMock(PersisterInterface::class);
        $this->checker = new IndexableChecker($this->createMock(ContainerInterface::class));

        $this->index = new IndexDefinition('products', 'products_v1', 'default', [], [], [], [], [], [], null);
        $this->config = new OrmIndexConfig($this->index, $this->persister, TestEntity::class, 'id', null);
    }

    public function testSubscribedEvents(): void
    {
        $listener = new OrmIndexListener([], $this->checker);

        self::assertSame([
            Events::postPersist,
            Events::postUpdate,
            Events::postRemove,
            Events::postFlush,
        ], $listener->getSubscribedEvents());
    }

    public function testPostPersistSchedulesIndexOnFlush(): void
    {
        $entity = new TestEntity(42, 'Widget');
        $listener = new OrmIndexListener([$this->config], $this->checker);

        $this->persister->expects(self::once())
            ->method('insertOne')
            ->with($this->index, $entity, '42');

        $listener->postPersist($this->createLifecycleEvent($entity));
        $listener->postFlush($this->createPostFlushEvent());
    }

    public function testPostUpdateSchedulesIndexOnFlush(): void
    {
        $entity = new TestEntity(42, 'Updated Widget');
        $listener = new OrmIndexListener([$this->config], $this->checker);

        $this->persister->expects(self::once())
            ->method('insertOne')
            ->with($this->index, $entity, '42');

        $listener->postUpdate($this->createLifecycleEvent($entity));
        $listener->postFlush($this->createPostFlushEvent());
    }

    public function testPostRemoveSchedulesDeleteOnFlush(): void
    {
        $entity = new TestEntity(42, 'Widget');
        $listener = new OrmIndexListener([$this->config], $this->checker);

        $this->persister->expects(self::once())
            ->method('deleteOne')
            ->with($this->index, '42');

        $listener->postRemove($this->createLifecycleEvent($entity));
        $listener->postFlush($this->createPostFlushEvent());
    }

    public function testNonMatchingEntityIsIgnored(): void
    {
        $entity = new \stdClass();
        $listener = new OrmIndexListener([$this->config], $this->checker);

        $this->persister->expects(self::never())->method('insertOne');

        $listener->postPersist($this->createLifecycleEvent($entity));
        $listener->postFlush($this->createPostFlushEvent());
    }

    public function testNonIndexableEntityIsIgnored(): void
    {
        $entity = new TestEntity(1, 'Draft', false);

        // Use an indexable callback that checks entity method 'isPublished' (returns false)
        $config = new OrmIndexConfig($this->index, $this->persister, TestEntity::class, 'id', 'isPublished');
        $listener = new OrmIndexListener([$config], $this->checker);

        $this->persister->expects(self::never())->method('insertOne');

        $listener->postPersist($this->createLifecycleEvent($entity));
        $listener->postFlush($this->createPostFlushEvent());
    }

    public function testQueuesAreClearedAfterFlush(): void
    {
        $entity = new TestEntity(1, 'Widget');
        $listener = new OrmIndexListener([$this->config], $this->checker);

        $this->persister->expects(self::once())->method('insertOne');

        $listener->postPersist($this->createLifecycleEvent($entity));
        $listener->postFlush($this->createPostFlushEvent());

        // Second flush should not re-process
        $this->persister->expects(self::never())->method('deleteOne');
        $listener->postFlush($this->createPostFlushEvent());
    }

    private function createLifecycleEvent(object $entity): LifecycleEventArgs
    {
        $om = $this->createMock(ObjectManager::class);

        return new LifecycleEventArgs($entity, $om);
    }

    private function createPostFlushEvent(): PostFlushEventArgs
    {
        return new PostFlushEventArgs($this->createMock(EntityManagerInterface::class));
    }
}

class TestEntity
{
    public function __construct(private readonly int $id, private readonly string $name, private readonly bool $published = true)
    {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isPublished(): bool
    {
        return $this->published;
    }
}
