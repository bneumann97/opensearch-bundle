<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Tests\Unit\Repository;

use Bneumann\OpensearchBundle\Exception\RepositoryException;
use Bneumann\OpensearchBundle\Repository\RepositoryManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class RepositoryManagerTest extends TestCase
{
    public function testGetReturnsRepository(): void
    {
        $repository = new \stdClass();

        $locator = $this->createMock(ContainerInterface::class);
        $locator->method('get')->with('opensearch.repository.products')->willReturn($repository);

        $manager = new RepositoryManager($locator, ['products' => 'opensearch.repository.products']);

        self::assertSame($repository, $manager->get('products'));
    }

    public function testGetThrowsOnUnknownIndex(): void
    {
        $locator = $this->createMock(ContainerInterface::class);
        $manager = new RepositoryManager($locator, []);

        $this->expectException(RepositoryException::class);
        $this->expectExceptionMessage('No repository configured for index "unknown".');

        $manager->get('unknown');
    }

    public function testHas(): void
    {
        $locator = $this->createMock(ContainerInterface::class);
        $manager = new RepositoryManager($locator, ['products' => 'opensearch.repository.products']);

        self::assertTrue($manager->has('products'));
        self::assertFalse($manager->has('orders'));
    }
}
