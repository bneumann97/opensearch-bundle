<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class PreRequestEvent extends Event
{
    /** @param array<string, mixed> $params */
    public function __construct(
        private readonly string $operation,
        private readonly array $params,
    ) {
    }

    public function getOperation(): string
    {
        return $this->operation;
    }

    /** @return array<string, mixed> */
    public function getParams(): array
    {
        return $this->params;
    }
}
