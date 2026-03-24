<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class PrePopulateEvent extends Event
{
    public function __construct(
        private readonly string $indexName,
        private readonly int $batchSize,
    ) {
    }

    public function getIndexName(): string
    {
        return $this->indexName;
    }

    public function getBatchSize(): int
    {
        return $this->batchSize;
    }
}
