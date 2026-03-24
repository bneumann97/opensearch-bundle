<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Finder;

use Bneumann\OpensearchBundle\Index\IndexDefinition;

interface HydratorInterface
{
    /** @param array<int, array<string, mixed>> $hits */
    public function hydrate(IndexDefinition $index, array $hits): array;
}
