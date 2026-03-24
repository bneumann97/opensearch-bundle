<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Tests\Unit\Index;

use Bneumann\OpensearchBundle\Client\ClientRegistryInterface;
use Bneumann\OpensearchBundle\Exception\IndexException;
use Bneumann\OpensearchBundle\Index\IndexDefinition;
use Bneumann\OpensearchBundle\Index\IndexManager;
use OpenSearch\Client;
use OpenSearch\Namespaces\IndicesNamespace;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class IndexManagerTest extends TestCase
{
    private ClientRegistryInterface&MockObject $clients;
    private IndicesNamespace&MockObject $indices;
    private IndexManager $manager;
    private IndexDefinition $index;

    protected function setUp(): void
    {
        $this->indices = $this->createMock(IndicesNamespace::class);

        $client = $this->createMock(Client::class);
        $client->method('indices')->willReturn($this->indices);

        $this->clients = $this->createMock(ClientRegistryInterface::class);
        $this->clients->method('get')->with('default')->willReturn($client);

        $this->manager = new IndexManager($this->clients);
        $this->index = new IndexDefinition(
            'products', 'products_v1', 'default',
            ['number_of_shards' => 1],
            ['properties' => ['name' => ['type' => 'text']]],
            ['products_alias' => []],
            [], [], [], null,
        );
    }

    public function testExists(): void
    {
        $this->indices->method('exists')->with(['index' => 'products_v1'])->willReturn(true);

        self::assertTrue($this->manager->exists($this->index));
    }

    public function testNotExists(): void
    {
        $this->indices->method('exists')->willReturn(false);

        self::assertFalse($this->manager->exists($this->index));
    }

    public function testCreate(): void
    {
        $this->indices->expects(self::once())
            ->method('create')
            ->with(self::callback(function (array $params) {
                return $params['index'] === 'products_v1'
                    && $params['body']['settings'] === ['number_of_shards' => 1]
                    && $params['body']['mappings'] === ['properties' => ['name' => ['type' => 'text']]]
                    && $params['body']['aliases'] === ['products_alias' => []];
            }));

        $this->manager->create($this->index);
    }

    public function testCreateWithForceDeletesExisting(): void
    {
        $this->indices->method('exists')->willReturn(true);
        $this->indices->expects(self::once())->method('delete');
        $this->indices->expects(self::once())->method('create');

        $this->manager->create($this->index, true);
    }

    public function testCreateWrapsException(): void
    {
        $this->indices->method('create')->willThrowException(new \RuntimeException('failed'));

        $this->expectException(IndexException::class);
        $this->expectExceptionMessage('Failed to create index "products_v1".');

        $this->manager->create($this->index);
    }

    public function testDeleteExistingIndex(): void
    {
        $this->indices->method('exists')->willReturn(true);
        $this->indices->expects(self::once())->method('delete')->with(['index' => 'products_v1']);

        $this->manager->delete($this->index);
    }

    public function testDeleteNonExistingIndexSkips(): void
    {
        $this->indices->method('exists')->willReturn(false);
        $this->indices->expects(self::never())->method('delete');

        $this->manager->delete($this->index);
    }

    public function testReset(): void
    {
        // delete check: exists returns true, then create
        $this->indices->method('exists')->willReturn(true);
        $this->indices->expects(self::once())->method('delete');
        $this->indices->expects(self::once())->method('create');

        $this->manager->reset($this->index);
    }

    public function testUpdateAliases(): void
    {
        $aliases = ['alias_a' => [], 'alias_b' => ['filter' => ['term' => ['status' => 'active']]]];

        $this->indices->expects(self::once())
            ->method('updateAliases')
            ->with(self::callback(function (array $params) {
                $actions = $params['body']['actions'];

                return count($actions) === 2
                    && $actions[0]['add']['index'] === 'products_v1'
                    && $actions[0]['add']['alias'] === 'alias_a'
                    && $actions[1]['add']['alias'] === 'alias_b';
            }));

        $this->manager->updateAliases($this->index, $aliases);
    }

    public function testUpdateAliasesEmptySkips(): void
    {
        $this->indices->expects(self::never())->method('updateAliases');

        $this->manager->updateAliases($this->index, []);
    }
}
