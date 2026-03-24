<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Tests\Unit\Finder;

use Bneumann\OpensearchBundle\Client\ClientRegistryInterface;
use Bneumann\OpensearchBundle\Exception\FinderException;
use Bneumann\OpensearchBundle\Finder\HydratorInterface;
use Bneumann\OpensearchBundle\Finder\MappedFinder;
use Bneumann\OpensearchBundle\Index\IndexDefinition;
use OpenSearch\Client;
use PHPUnit\Framework\TestCase;

final class MappedFinderTest extends TestCase
{
    public function testFindHydratesResults(): void
    {
        $hits = [['_id' => '1', '_source' => ['name' => 'A']]];
        $response = ['hits' => ['hits' => $hits]];
        $hydrated = [['name' => 'A']];

        $client = $this->createMock(Client::class);
        $client->method('search')->willReturn($response);

        $clients = $this->createMock(ClientRegistryInterface::class);
        $clients->method('get')->willReturn($client);

        $index = new IndexDefinition('products', 'products_v1', 'default', [], [], [], [], [], [], null);

        $hydrator = $this->createMock(HydratorInterface::class);
        $hydrator->expects(self::once())
            ->method('hydrate')
            ->with($index, $hits)
            ->willReturn($hydrated);

        $finder = new MappedFinder($clients, $index, $hydrator);

        self::assertSame($hydrated, $finder->find(['query' => ['match_all' => new \stdClass()]]));
    }

    public function testFindHandsMissingHitsAsEmpty(): void
    {
        $client = $this->createMock(Client::class);
        $client->method('search')->willReturn(['hits' => []]);

        $clients = $this->createMock(ClientRegistryInterface::class);
        $clients->method('get')->willReturn($client);

        $index = new IndexDefinition('products', 'products_v1', 'default', [], [], [], [], [], [], null);

        $hydrator = $this->createMock(HydratorInterface::class);
        $hydrator->expects(self::once())
            ->method('hydrate')
            ->with($index, [])
            ->willReturn([]);

        $finder = new MappedFinder($clients, $index, $hydrator);

        self::assertSame([], $finder->find([]));
    }

    public function testFindWrapsException(): void
    {
        $client = $this->createMock(Client::class);
        $client->method('search')->willThrowException(new \RuntimeException('fail'));

        $clients = $this->createMock(ClientRegistryInterface::class);
        $clients->method('get')->willReturn($client);

        $index = new IndexDefinition('products', 'products_v1', 'default', [], [], [], [], [], [], null);
        $hydrator = $this->createMock(HydratorInterface::class);

        $finder = new MappedFinder($clients, $index, $hydrator);

        $this->expectException(FinderException::class);
        $this->expectExceptionMessage('Search failed on index "products_v1".');

        $finder->find([]);
    }
}
