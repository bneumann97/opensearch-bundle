<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Tests\Unit\Client;

use Bneumann\OpensearchBundle\Client\ClientRegistry;
use Bneumann\OpensearchBundle\Exception\ClientException;
use OpenSearch\Client;
use PHPUnit\Framework\TestCase;

final class ClientRegistryTest extends TestCase
{
    public function testGetReturnsClient(): void
    {
        $client = $this->createMock(Client::class);
        $registry = new ClientRegistry(['default' => $client], 'default');

        self::assertSame($client, $registry->get('default'));
    }

    public function testGetThrowsOnUnknownClient(): void
    {
        $registry = new ClientRegistry([], 'default');

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('OpenSearch client "unknown" is not configured.');

        $registry->get('unknown');
    }

    public function testGetDefaultReturnsDefaultClient(): void
    {
        $client = $this->createMock(Client::class);
        $registry = new ClientRegistry(['my_client' => $client], 'my_client');

        self::assertSame($client, $registry->getDefault());
    }

    public function testHasReturnsTrueForExistingClient(): void
    {
        $client = $this->createMock(Client::class);
        $registry = new ClientRegistry(['default' => $client], 'default');

        self::assertTrue($registry->has('default'));
    }

    public function testHasReturnsFalseForMissingClient(): void
    {
        $registry = new ClientRegistry([], 'default');

        self::assertFalse($registry->has('missing'));
    }
}
