<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Tests\Unit\Repository;

use Bneumann\OpensearchBundle\Finder\FinderInterface;
use Bneumann\OpensearchBundle\Repository\DefaultRepository;
use PHPUnit\Framework\TestCase;

final class DefaultRepositoryTest extends TestCase
{
    public function testSearchDelegatesToFinder(): void
    {
        $query = ['query' => ['match_all' => new \stdClass()]];
        $expected = [['name' => 'Product A']];

        $finder = $this->createMock(FinderInterface::class);
        $finder->expects(self::once())
            ->method('find')
            ->with($query)
            ->willReturn($expected);

        $repository = new DefaultRepository($finder);

        self::assertSame($expected, $repository->search($query));
    }
}
