<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class PostTransformEvent extends Event
{
    public function __construct(
        private readonly object $object,
        private readonly string $indexName,
        private array $data,
    ) {
    }

    public function getObject(): object
    {
        return $this->object;
    }

    public function getIndexName(): string
    {
        return $this->indexName;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }
}
