<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Client;

use Bneumann\OpensearchBundle\Event\PostRequestEvent;
use Bneumann\OpensearchBundle\Event\PreRequestEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Throwable;

trait ClientCallerTrait
{
    private ?EventDispatcherInterface $eventDispatcher = null;

    public function setEventDispatcher(?EventDispatcherInterface $dispatcher): void
    {
        $this->eventDispatcher = $dispatcher;
    }

    /**
     * @param callable $callable
     * @param array<string, mixed> $params
     */
    private function callClient(string $operation, array $params, callable $callable): mixed
    {
        $this->eventDispatcher?->dispatch(new PreRequestEvent($operation, $params));

        try {
            $response = $callable($params);
        } catch (Throwable $e) {
            $this->eventDispatcher?->dispatch(new PostRequestEvent($operation, $params, null, $e));
            throw $e;
        }

        $this->eventDispatcher?->dispatch(new PostRequestEvent($operation, $params, $response, null));

        return $response;
    }
}
