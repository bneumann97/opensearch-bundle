<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Repository;

use Bneumann\OpensearchBundle\Exception\RepositoryException;
use Psr\Container\ContainerInterface;

final class RepositoryManager implements RepositoryManagerInterface
{
    /** @var array<string, string> */
    private array $repositories;

    public function __construct(private readonly ContainerInterface $locator, array $repositories)
    {
        $this->repositories = $repositories;
    }

    public function get(string $indexName): object
    {
        if (!$this->has($indexName)) {
            throw new RepositoryException(sprintf('No repository configured for index "%s".', $indexName));
        }

        return $this->locator->get($this->repositories[$indexName]);
    }

    public function has(string $indexName): bool
    {
        return array_key_exists($indexName, $this->repositories);
    }
}
