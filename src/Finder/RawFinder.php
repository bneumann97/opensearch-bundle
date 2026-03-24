<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Finder;

use Bneumann\OpensearchBundle\Client\ClientCallerTrait;
use Bneumann\OpensearchBundle\Client\ClientRegistryInterface;
use Bneumann\OpensearchBundle\Exception\FinderException;
use Bneumann\OpensearchBundle\Index\IndexDefinition;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Throwable;

final class RawFinder implements FinderInterface
{
    use ClientCallerTrait;

    public function __construct(
        private readonly ClientRegistryInterface $clients,
        private readonly IndexDefinition $index,
        ?EventDispatcherInterface $dispatcher = null,
    ) {
        $this->setEventDispatcher($dispatcher);
    }

    public function find(array $query): iterable
    {
        $client = $this->clients->get($this->index->getClient());
        $params = [
            'index' => $this->index->getIndexName(),
            'body' => $query,
        ];

        try {
            return $this->callClient('search', $params, fn (array $params) => $client->search($params));
        } catch (Throwable $e) {
            throw new FinderException(sprintf('Search failed on index "%s".', $this->index->getIndexName()), 0, $e);
        }
    }
}
