<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Finder;

interface FinderInterface
{
    /** @return iterable<mixed> */
    public function find(array $query): iterable;
}
