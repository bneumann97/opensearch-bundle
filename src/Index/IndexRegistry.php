<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Index;

use Bneumann\OpensearchBundle\Exception\IndexException;

final class IndexRegistry
{
    /** @var array<string, IndexDefinition> */
    private array $indexes;

    public function __construct(array $indexes)
    {
        $this->indexes = $indexes;
    }

    public function get(string $name): IndexDefinition
    {
        if (!isset($this->indexes[$name])) {
            throw new IndexException(sprintf('OpenSearch index "%s" is not configured.', $name));
        }

        return $this->indexes[$name];
    }

    /** @return array<string, IndexDefinition> */
    public function all(): array
    {
        return $this->indexes;
    }
}
