<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Throwable;

final class PostRequestEvent extends Event
{
    /** @param array<string, mixed> $params */
    public function __construct(
        private readonly string $operation,
        private readonly array $params,
        private readonly mixed $response,
        private readonly ?Throwable $exception,
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

    public function getResponse(): mixed
    {
        return $this->response;
    }

    public function getException(): ?Throwable
    {
        return $this->exception;
    }
}
