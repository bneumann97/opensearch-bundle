<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Finder;

use Bneumann\OpensearchBundle\Index\IndexDefinition;

final class ArrayHydrator implements HydratorInterface
{
    public function hydrate(IndexDefinition $index, array $hits): array
    {
        $results = [];
        foreach ($hits as $hit) {
            $results[] = $hit['_source'] ?? $hit;
        }

        return $results;
    }
}
