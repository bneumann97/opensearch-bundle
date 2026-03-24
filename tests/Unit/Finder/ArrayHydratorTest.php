<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Tests\Unit\Finder;

use Bneumann\OpensearchBundle\Finder\ArrayHydrator;
use Bneumann\OpensearchBundle\Index\IndexDefinition;
use PHPUnit\Framework\TestCase;

final class ArrayHydratorTest extends TestCase
{
    private ArrayHydrator $hydrator;
    private IndexDefinition $index;

    protected function setUp(): void
    {
        $this->hydrator = new ArrayHydrator();
        $this->index = new IndexDefinition('test', 'test', 'default', [], [], [], [], [], [], null);
    }

    public function testHydrateExtractsSource(): void
    {
        $hits = [
            ['_id' => '1', '_source' => ['name' => 'Product A']],
            ['_id' => '2', '_source' => ['name' => 'Product B']],
        ];

        $result = $this->hydrator->hydrate($this->index, $hits);

        self::assertCount(2, $result);
        self::assertSame(['name' => 'Product A'], $result[0]);
        self::assertSame(['name' => 'Product B'], $result[1]);
    }

    public function testHydrateFallsBackToHitWhenNoSource(): void
    {
        $hits = [
            ['_id' => '1', 'fields' => ['name' => ['Product A']]],
        ];

        $result = $this->hydrator->hydrate($this->index, $hits);

        self::assertSame($hits[0], $result[0]);
    }

    public function testHydrateEmptyHits(): void
    {
        $result = $this->hydrator->hydrate($this->index, []);

        self::assertSame([], $result);
    }
}
