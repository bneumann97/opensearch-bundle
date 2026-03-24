<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Tests\Unit\Index;

use Bneumann\OpensearchBundle\Index\IndexNameGenerator;
use PHPUnit\Framework\TestCase;

final class IndexNameGeneratorTest extends TestCase
{
    public function testGeneratePhysicalNameContainsBaseName(): void
    {
        $generator = new IndexNameGenerator();
        $name = $generator->generatePhysicalName('products');

        self::assertStringStartsWith('products_', $name);
    }

    public function testGeneratePhysicalNameContainsTimestamp(): void
    {
        $generator = new IndexNameGenerator();
        $name = $generator->generatePhysicalName('products');

        self::assertMatchesRegularExpression('/^products_\d{14}$/', $name);
    }
}
