<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Index;

interface IndexManagerInterface
{
    public function exists(IndexDefinition $index): bool;

    public function create(IndexDefinition $index, bool $force = false): void;

    public function reset(IndexDefinition $index): void;

    public function delete(IndexDefinition $index): void;

    public function updateAliases(IndexDefinition $index, array $aliases): void;
}
