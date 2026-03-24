<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Tests\Unit\Provider;

use Bneumann\OpensearchBundle\Exception\PersisterException;
use Bneumann\OpensearchBundle\Provider\ProviderInterface;
use Bneumann\OpensearchBundle\Provider\ProviderRegistry;
use PHPUnit\Framework\TestCase;

final class ProviderRegistryTest extends TestCase
{
    public function testGetReturnsProvider(): void
    {
        $provider = $this->createMock(ProviderInterface::class);
        $registry = new ProviderRegistry(['products' => $provider]);

        self::assertSame($provider, $registry->get('products'));
    }

    public function testGetThrowsOnUnknownIndex(): void
    {
        $registry = new ProviderRegistry([]);

        $this->expectException(PersisterException::class);
        $this->expectExceptionMessage('No provider configured for index "unknown".');

        $registry->get('unknown');
    }

    public function testHas(): void
    {
        $provider = $this->createMock(ProviderInterface::class);
        $registry = new ProviderRegistry(['products' => $provider]);

        self::assertTrue($registry->has('products'));
        self::assertFalse($registry->has('orders'));
    }
}
