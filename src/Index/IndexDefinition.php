<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Index;

final class IndexDefinition
{
    public function __construct(
        private readonly string $name,
        private readonly string $indexName,
        private readonly string $client,
        private readonly array $settings,
        private readonly array $mappings,
        private readonly array $aliases,
        private readonly array $serializer,
        private readonly array $persistence,
        private readonly array $finder,
        private readonly ?string $repositoryClass,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getIndexName(): string
    {
        return $this->indexName;
    }

    public function getClient(): string
    {
        return $this->client;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function getMappings(): array
    {
        return $this->mappings;
    }

    public function getAliases(): array
    {
        return $this->aliases;
    }

    public function getSerializerConfig(): array
    {
        return $this->serializer;
    }

    public function getPersistenceConfig(): array
    {
        return $this->persistence;
    }

    public function getFinderConfig(): array
    {
        return $this->finder;
    }

    public function getRepositoryClass(): ?string
    {
        return $this->repositoryClass;
    }
}
