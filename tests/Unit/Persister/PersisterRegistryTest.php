<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Tests\Unit\Persister;

use Bneumann\OpensearchBundle\Exception\PersisterException;
use Bneumann\OpensearchBundle\Persister\PersisterInterface;
use Bneumann\OpensearchBundle\Persister\PersisterRegistry;
use PHPUnit\Framework\TestCase;

final class PersisterRegistryTest extends TestCase
{
    public function testGetReturnsPersister(): void
    {
        $persister = $this->createMock(PersisterInterface::class);
        $registry = new PersisterRegistry(['products' => $persister]);

        self::assertSame($persister, $registry->get('products'));
    }

    public function testGetThrowsOnUnknownIndex(): void
    {
        $registry = new PersisterRegistry([]);

        $this->expectException(PersisterException::class);
        $this->expectExceptionMessage('No persister configured for index "unknown".');

        $registry->get('unknown');
    }

    public function testHas(): void
    {
        $persister = $this->createMock(PersisterInterface::class);
        $registry = new PersisterRegistry(['products' => $persister]);

        self::assertTrue($registry->has('products'));
        self::assertFalse($registry->has('orders'));
    }
}
