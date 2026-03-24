<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Repository;

use Bneumann\OpensearchBundle\Finder\FinderInterface;

abstract class Repository
{
    public function __construct(protected readonly FinderInterface $finder)
    {
    }

    /** @return iterable<mixed> */
    public function search(array $query): iterable
    {
        return $this->finder->find($query);
    }
}
