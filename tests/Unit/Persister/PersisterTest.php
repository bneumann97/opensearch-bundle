<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Tests\Unit\Persister;

use Bneumann\OpensearchBundle\Client\ClientRegistryInterface;
use Bneumann\OpensearchBundle\Exception\PersisterException;
use Bneumann\OpensearchBundle\Index\IndexDefinition;
use Bneumann\OpensearchBundle\Persister\Persister;
use Bneumann\OpensearchBundle\Transformer\TransformerInterface;
use OpenSearch\Client;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PersisterTest extends TestCase
{
    private ClientRegistryInterface&MockObject $clients;
    private TransformerInterface&MockObject $transformer;
    private Client&MockObject $client;
    private IndexDefinition $index;
    private Persister $persister;

    protected function setUp(): void
    {
        $this->client = $this->createMock(Client::class);
        $this->clients = $this->createMock(ClientRegistryInterface::class);
        $this->clients->method('get')->with('default')->willReturn($this->client);

        $this->transformer = $this->createMock(TransformerInterface::class);
        $this->index = new IndexDefinition('products', 'products_v1', 'default', [], [], [], [], [], [], null);

        $this->persister = new Persister($this->clients, $this->transformer);
    }

    public function testInsertOne(): void
    {
        $object = new \stdClass();
        $this->transformer->method('transform')->willReturn(['name' => 'Product']);

        $this->client->expects(self::once())
            ->method('index')
            ->with(self::callback(fn (array $params) => $params['index'] === 'products_v1'
                && $params['body'] === ['name' => 'Product']
                && !isset($params['id'])
            ));

        $this->persister->insertOne($this->index, $object);
    }

    public function testInsertOneWithId(): void
    {
        $object = new \stdClass();
        $this->transformer->method('transform')->willReturn(['name' => 'Product']);

        $this->client->expects(self::once())
            ->method('index')
            ->with(self::callback(fn (array $params) => $params['id'] === '42'));

        $this->persister->insertOne($this->index, $object, '42');
    }

    public function testInsertOneWrapsException(): void
    {
        $object = new \stdClass();
        $this->transformer->method('transform')->willReturn(['name' => 'Product']);
        $this->client->method('index')->willThrowException(new \RuntimeException('connection failed'));

        $this->expectException(PersisterException::class);
        $this->expectExceptionMessage('Failed to index document into "products_v1".');

        $this->persister->insertOne($this->index, $object);
    }

    public function testDeleteOne(): void
    {
        $this->client->expects(self::once())
            ->method('delete')
            ->with(['index' => 'products_v1', 'id' => '42']);

        $this->persister->deleteOne($this->index, '42');
    }

    public function testDeleteOneWrapsException(): void
    {
        $this->client->method('delete')->willThrowException(new \RuntimeException('not found'));

        $this->expectException(PersisterException::class);
        $this->expectExceptionMessage('Failed to delete document "42" from "products_v1".');

        $this->persister->deleteOne($this->index, '42');
    }

    public function testInsertMany(): void
    {
        $obj1 = new \stdClass();
        $obj2 = new \stdClass();

        $this->transformer->method('transform')
            ->willReturnOnConsecutiveCalls(['name' => 'A'], ['name' => 'B']);

        $this->client->expects(self::once())
            ->method('bulk')
            ->with(self::callback(function (array $params) {
                $body = $params['body'];

                return count($body) === 4
                    && $body[0] === ['index' => ['_index' => 'products_v1']]
                    && $body[1] === ['name' => 'A']
                    && $body[2] === ['index' => ['_index' => 'products_v1']]
                    && $body[3] === ['name' => 'B'];
            }));

        $this->persister->insertMany($this->index, [$obj1, $obj2]);
    }

    public function testInsertManyWithIdResolver(): void
    {
        $obj = new \stdClass();
        $obj->id = 99;

        $this->transformer->method('transform')->willReturn(['name' => 'A']);

        $this->client->expects(self::once())
            ->method('bulk')
            ->with(self::callback(fn (array $params) => $params['body'][0]['index']['_id'] === '99'));

        $this->persister->insertMany($this->index, [$obj], fn ($o) => $o->id);
    }

    public function testInsertManyEmptyIterableDoesNothing(): void
    {
        $this->client->expects(self::never())->method('bulk');

        $this->persister->insertMany($this->index, []);
    }

    public function testInsertManyWrapsException(): void
    {
        $obj = new \stdClass();
        $this->transformer->method('transform')->willReturn(['name' => 'A']);
        $this->client->method('bulk')->willThrowException(new \RuntimeException('bulk failed'));

        $this->expectException(PersisterException::class);
        $this->expectExceptionMessage('Failed to bulk index documents into "products_v1".');

        $this->persister->insertMany($this->index, [$obj]);
    }
}
