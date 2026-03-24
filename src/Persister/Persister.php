<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Persister;

use Bneumann\OpensearchBundle\Client\ClientCallerTrait;
use Bneumann\OpensearchBundle\Client\ClientRegistryInterface;
use Bneumann\OpensearchBundle\Event\PostTransformEvent;
use Bneumann\OpensearchBundle\Event\PreTransformEvent;
use Bneumann\OpensearchBundle\Exception\PersisterException;
use Bneumann\OpensearchBundle\Index\IndexDefinition;
use Bneumann\OpensearchBundle\Transformer\TransformerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Throwable;

final class Persister implements PersisterInterface
{
    use ClientCallerTrait;

    public function __construct(
        private readonly ClientRegistryInterface $clients,
        private readonly TransformerInterface $transformer,
        ?EventDispatcherInterface $dispatcher = null,
    ) {
        $this->setEventDispatcher($dispatcher);
    }

    public function insertOne(IndexDefinition $index, object $object, ?string $id = null): void
    {
        $client = $this->clients->get($index->getClient());

        $data = $this->transformObject($object, $index);

        $params = [
            'index' => $index->getIndexName(),
            'body' => $data,
        ];

        if ($id !== null) {
            $params['id'] = $id;
        }

        try {
            $this->callClient('index', $params, fn (array $params) => $client->index($params));
        } catch (Throwable $e) {
            throw new PersisterException(sprintf('Failed to index document into "%s".', $index->getIndexName()), 0, $e);
        }
    }

    public function deleteOne(IndexDefinition $index, string $id): void
    {
        $client = $this->clients->get($index->getClient());

        $params = [
            'index' => $index->getIndexName(),
            'id' => $id,
        ];

        try {
            $this->callClient('delete', $params, fn (array $params) => $client->delete($params));
        } catch (Throwable $e) {
            throw new PersisterException(sprintf('Failed to delete document "%s" from "%s".', $id, $index->getIndexName()), 0, $e);
        }
    }

    public function insertMany(IndexDefinition $index, iterable $objects, ?callable $idResolver = null): void
    {
        $client = $this->clients->get($index->getClient());

        $body = [];
        foreach ($objects as $object) {
            $data = $this->transformObject($object, $index);
            $action = ['index' => ['_index' => $index->getIndexName()]];

            if ($idResolver !== null) {
                $id = (string) $idResolver($object);
                $action['index']['_id'] = $id;
            }

            $body[] = $action;
            $body[] = $data;
        }

        if (empty($body)) {
            return;
        }

        $params = ['body' => $body];

        try {
            $this->callClient('bulk', $params, fn (array $params) => $client->bulk($params));
        } catch (Throwable $e) {
            throw new PersisterException(sprintf('Failed to bulk index documents into "%s".', $index->getIndexName()), 0, $e);
        }
    }

    private function transformObject(object $object, IndexDefinition $index): array
    {
        $preEvent = new PreTransformEvent($object, $index->getIndexName());
        $this->eventDispatcher?->dispatch($preEvent);

        $data = $this->transformer->transform($object, $index);

        $postEvent = new PostTransformEvent($object, $index->getIndexName(), $data);
        $this->eventDispatcher?->dispatch($postEvent);

        return $postEvent->getData();
    }
}
