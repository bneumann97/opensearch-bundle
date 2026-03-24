<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Tests\Unit\Doctrine;

use Bneumann\OpensearchBundle\Doctrine\Listener\IndexableChecker;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class IndexableCheckerTest extends TestCase
{
    public function testNullIndexableReturnsTrue(): void
    {
        $checker = new IndexableChecker($this->createMock(ContainerInterface::class));

        self::assertTrue($checker->isIndexable(new \stdClass(), null));
    }

    public function testEntityMethodReturnsTrue(): void
    {
        $entity = new class {
            public function isPublished(): bool
            {
                return true;
            }
        };

        $checker = new IndexableChecker($this->createMock(ContainerInterface::class));

        self::assertTrue($checker->isIndexable($entity, 'isPublished'));
    }

    public function testEntityMethodReturnsFalse(): void
    {
        $entity = new class {
            public function isPublished(): bool
            {
                return false;
            }
        };

        $checker = new IndexableChecker($this->createMock(ContainerInterface::class));

        self::assertFalse($checker->isIndexable($entity, 'isPublished'));
    }

    public function testServiceCallback(): void
    {
        $callable = fn (object $entity) => true;

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with('my_checker')->willReturn($callable);

        $checker = new IndexableChecker($container);

        self::assertTrue($checker->isIndexable(new \stdClass(), '@my_checker'));
    }

    public function testStaticMethodCallback(): void
    {
        self::assertTrue(
            (new IndexableChecker($this->createMock(ContainerInterface::class)))
                ->isIndexable(new \stdClass(), self::class . '::staticIndexable')
        );
    }

    public static function staticIndexable(object $entity): bool
    {
        return true;
    }

    public function testUnknownCallbackDefaultsToTrue(): void
    {
        $checker = new IndexableChecker($this->createMock(ContainerInterface::class));

        self::assertTrue($checker->isIndexable(new \stdClass(), 'nonExistentMethodOrFunction'));
    }
}
