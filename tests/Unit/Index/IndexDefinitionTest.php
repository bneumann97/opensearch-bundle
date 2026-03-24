<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Tests\Unit\Index;

use Bneumann\OpensearchBundle\Index\IndexDefinition;
use PHPUnit\Framework\TestCase;

final class IndexDefinitionTest extends TestCase
{
    public function testGetters(): void
    {
        $definition = new IndexDefinition(
            name: 'products',
            indexName: 'products_v1',
            client: 'default',
            settings: ['number_of_shards' => 1],
            mappings: ['properties' => ['title' => ['type' => 'text']]],
            aliases: ['products_alias' => []],
            serializer: ['enabled' => false],
            persistence: ['driver' => 'orm', 'model' => 'App\\Entity\\Product'],
            finder: ['hydration' => 'array'],
            repositoryClass: 'App\\Repository\\ProductRepository',
        );

        self::assertSame('products', $definition->getName());
        self::assertSame('products_v1', $definition->getIndexName());
        self::assertSame('default', $definition->getClient());
        self::assertSame(['number_of_shards' => 1], $definition->getSettings());
        self::assertSame(['properties' => ['title' => ['type' => 'text']]], $definition->getMappings());
        self::assertSame(['products_alias' => []], $definition->getAliases());
        self::assertSame(['enabled' => false], $definition->getSerializerConfig());
        self::assertSame(['driver' => 'orm', 'model' => 'App\\Entity\\Product'], $definition->getPersistenceConfig());
        self::assertSame(['hydration' => 'array'], $definition->getFinderConfig());
        self::assertSame('App\\Repository\\ProductRepository', $definition->getRepositoryClass());
    }

    public function testNullableRepositoryClass(): void
    {
        $definition = new IndexDefinition('test', 'test', 'default', [], [], [], [], [], [], null);

        self::assertNull($definition->getRepositoryClass());
    }
}
