<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Tests\Unit\Index;

use Bneumann\OpensearchBundle\Exception\IndexException;
use Bneumann\OpensearchBundle\Index\IndexDefinition;
use Bneumann\OpensearchBundle\Index\IndexRegistry;
use PHPUnit\Framework\TestCase;

final class IndexRegistryTest extends TestCase
{
    public function testGetReturnsDefinition(): void
    {
        $definition = $this->createIndexDefinition('products');
        $registry = new IndexRegistry(['products' => $definition]);

        self::assertSame($definition, $registry->get('products'));
    }

    public function testGetThrowsOnUnknownIndex(): void
    {
        $registry = new IndexRegistry([]);

        $this->expectException(IndexException::class);
        $this->expectExceptionMessage('OpenSearch index "unknown" is not configured.');

        $registry->get('unknown');
    }

    public function testAllReturnsAllDefinitions(): void
    {
        $products = $this->createIndexDefinition('products');
        $orders = $this->createIndexDefinition('orders');
        $registry = new IndexRegistry(['products' => $products, 'orders' => $orders]);

        self::assertCount(2, $registry->all());
        self::assertSame($products, $registry->all()['products']);
        self::assertSame($orders, $registry->all()['orders']);
    }

    private function createIndexDefinition(string $name): IndexDefinition
    {
        return new IndexDefinition($name, $name, 'default', [], [], [], [], [], [], null);
    }
}
