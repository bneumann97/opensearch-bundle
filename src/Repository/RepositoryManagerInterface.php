<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Repository;

interface RepositoryManagerInterface
{
    public function get(string $indexName): object;

    public function has(string $indexName): bool;
}
