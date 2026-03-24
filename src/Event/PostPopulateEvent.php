<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class PostPopulateEvent extends Event
{
    public function __construct(
        private readonly string $indexName,
        private readonly int $processed,
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
}
