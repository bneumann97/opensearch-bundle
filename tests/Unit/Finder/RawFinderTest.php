<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Tests\Unit\Finder;

use Bneumann\OpensearchBundle\Client\ClientRegistryInterface;
use Bneumann\OpensearchBundle\Exception\FinderException;
use Bneumann\OpensearchBundle\Finder\RawFinder;
use Bneumann\OpensearchBundle\Index\IndexDefinition;
use OpenSearch\Client;
use PHPUnit\Framework\TestCase;

final class RawFinderTest extends TestCase
{
    public function testFindReturnsRawResponse(): void
    {
        $response = ['hits' => ['hits' => [['_id' => '1', '_source' => ['name' => 'A']]]]];

        $client = $this->createMock(Client::class);
        $client->method('search')->willReturn($response);

        $clients = $this->createMock(ClientRegistryInterface::class);
        $clients->method('get')->with('default')->willReturn($client);

        $index = new IndexDefinition('products', 'products_v1', 'default', [], [], [], [], [], [], null);
        $finder = new RawFinder($clients, $index);

        self::assertSame($response, $finder->find(['query' => ['match_all' => new \stdClass()]]));
    }

    public function testFindWrapsException(): void
    {
        $client = $this->createMock(Client::class);
        $client->method('search')->willThrowException(new \RuntimeException('connection error'));

        $clients = $this->createMock(ClientRegistryInterface::class);
        $clients->method('get')->willReturn($client);

        $index = new IndexDefinition('products', 'products_v1', 'default', [], [], [], [], [], [], null);
        $finder = new RawFinder($clients, $index);

        $this->expectException(FinderException::class);
        $this->expectExceptionMessage('Search failed on index "products_v1".');

        $finder->find([]);
    }
}
