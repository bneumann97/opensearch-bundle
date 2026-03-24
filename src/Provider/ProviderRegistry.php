<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Provider;

use Bneumann\OpensearchBundle\Exception\PersisterException;

final class ProviderRegistry
{
    /** @var array<string, ProviderInterface> */
    private array $providers;

    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }

    public function get(string $indexName): ProviderInterface
    {
        if (!isset($this->providers[$indexName])) {
            throw new PersisterException(sprintf('No provider configured for index "%s".', $indexName));
        }

        return $this->providers[$indexName];
    }

    public function has(string $indexName): bool
    {
        return array_key_exists($indexName, $this->providers);
    }
}
