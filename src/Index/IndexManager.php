<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Index;

use Bneumann\OpensearchBundle\Client\ClientCallerTrait;
use Bneumann\OpensearchBundle\Client\ClientRegistryInterface;
use Bneumann\OpensearchBundle\Exception\IndexException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Throwable;

final class IndexManager implements IndexManagerInterface
{
    use ClientCallerTrait;

    public function __construct(private readonly ClientRegistryInterface $clients, ?EventDispatcherInterface $dispatcher = null)
    {
        $this->setEventDispatcher($dispatcher);
    }

    public function exists(IndexDefinition $index): bool
    {
        $client = $this->clients->get($index->getClient());

        return (bool) $this->callClient('indices.exists', ['index' => $index->getIndexName()],
            fn (array $params) => $client->indices()->exists($params)
        );
    }

    public function create(IndexDefinition $index, bool $force = false): void
    {
        $client = $this->clients->get($index->getClient());

        if ($force && $this->exists($index)) {
            $this->delete($index);
        }

        $params = [
            'index' => $index->getIndexName(),
            'body' => [
                'settings' => $index->getSettings(),
                'mappings' => $index->getMappings(),
                'aliases' => $index->getAliases(),
            ],
        ];

        try {
            $this->callClient('indices.create', $params,
                fn (array $params) => $client->indices()->create($params)
            );
        } catch (Throwable $e) {
            throw new IndexException(sprintf('Failed to create index "%s".', $index->getIndexName()), 0, $e);
        }
    }

    public function reset(IndexDefinition $index): void
    {
        $this->delete($index);
        $this->create($index, false);
    }

    public function delete(IndexDefinition $index): void
    {
        $client = $this->clients->get($index->getClient());

        if (!$this->exists($index)) {
            return;
        }

        try {
            $this->callClient('indices.delete', ['index' => $index->getIndexName()],
                fn (array $params) => $client->indices()->delete($params)
            );
        } catch (Throwable $e) {
            throw new IndexException(sprintf('Failed to delete index "%s".', $index->getIndexName()), 0, $e);
        }
    }

    public function updateAliases(IndexDefinition $index, array $aliases): void
    {
        $client = $this->clients->get($index->getClient());

        $actions = [];
        foreach ($aliases as $alias => $definition) {
            $actions[] = ['add' => ['index' => $index->getIndexName(), 'alias' => $alias] + (array) $definition];
        }

        if (empty($actions)) {
            return;
        }

        $params = ['body' => ['actions' => $actions]];

        try {
            $this->callClient('indices.update_aliases', $params,
                fn (array $params) => $client->indices()->updateAliases($params)
            );
        } catch (Throwable $e) {
            throw new IndexException(sprintf('Failed to update aliases for index "%s".', $index->getIndexName()), 0, $e);
        }
    }
}
