<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Persister;

use Bneumann\OpensearchBundle\Exception\PersisterException;

final class PersisterRegistry
{
    /** @var array<string, PersisterInterface> */
    private array $persisters;

    public function __construct(array $persisters)
    {
        $this->persisters = $persisters;
    }

    public function get(string $indexName): PersisterInterface
    {
        if (!isset($this->persisters[$indexName])) {
            throw new PersisterException(sprintf('No persister configured for index "%s".', $indexName));
        }

        return $this->persisters[$indexName];
    }

    public function has(string $indexName): bool
    {
        return array_key_exists($indexName, $this->persisters);
    }
}
