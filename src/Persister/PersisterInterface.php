<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Persister;

use Bneumann\OpensearchBundle\Index\IndexDefinition;

interface PersisterInterface
{
    public function insertOne(IndexDefinition $index, object $object, ?string $id = null): void;

    public function deleteOne(IndexDefinition $index, string $id): void;

    /** @param iterable<object> $objects */
    public function insertMany(IndexDefinition $index, iterable $objects, ?callable $idResolver = null): void;
}
