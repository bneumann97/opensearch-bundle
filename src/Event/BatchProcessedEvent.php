<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class BatchProcessedEvent extends Event
{
    public function __construct(
        private readonly string $indexName,
        private readonly int $processed,
        private readonly int $batchSize,
    ) {
    }

    public function getIndexName(): string
    {
        return $this->indexName;
    }

    public function getProcessed(): int
    {
        return $this->processed;
    }

    public function getBatchSize(): int
    {
        return $this->batchSize;
    }
}
