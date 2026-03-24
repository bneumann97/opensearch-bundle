# OpenSearch Bundle

A Symfony bundle providing native OpenSearch integration. Inspired by FOSElasticaBundle, built for OpenSearch from the ground up.

## Features

- Multiple OpenSearch client support
- Automatic Doctrine ORM synchronization
- Blue/green reindexing with alias switching
- Configurable object transformation (reflection, Symfony Serializer)
- Repository pattern for search queries
- Index templates
- Event-driven extensibility (7 lifecycle events)
- Console commands for index management

## Requirements

- PHP 8.1+
- Symfony 6.4 / 7.x / 8.x
- OpenSearch 2.x

## Installation

```bash
composer require bneumann/opensearch-bundle
```

## Quick Start

Configure a client and an index:

```yaml
# config/packages/opensearch.yaml
opensearch:
  clients:
    default:
      hosts: ['https://localhost:9200']
      username: '%env(OPENSEARCH_USER)%'
      password: '%env(OPENSEARCH_PASS)%'
      ssl_verification: true
  indexes:
    products:
      index_name: 'products_%kernel.environment%'
      settings:
        number_of_shards: 1
        number_of_replicas: 0
      mappings:
        properties:
          name: { type: 'text' }
          sku: { type: 'keyword' }
      finder:
        hydration: orm
      persistence:
        driver: orm
        model: App\Entity\Product
        provider: true
        listener: true
        identifier: id
```

Create and populate the index:

```bash
bin/console opensearch:index:create products
bin/console opensearch:index:populate products
```

Search using the finder service:

```php
use Bneumann\OpensearchBundle\Finder\FinderInterface;

public function __construct(private FinderInterface $finder) {}

$results = $this->finder->find([
    'query' => [
        'match' => ['name' => 'bike'],
    ],
]);
```

Or use the repository pattern:

```php
use Bneumann\OpensearchBundle\Repository\Repository;

final class ProductRepository extends Repository
{
    public function findBySku(string $sku): iterable
    {
        return $this->search([
            'query' => ['term' => ['sku' => $sku]],
        ]);
    }
}
```

## Documentation

- [Setup](doc/setup.md)
- [Usage](doc/usage.md)
- [Indexes](doc/indexes.md)
- [Serializer](doc/serializer.md)
- [Templates](doc/templates.md)
- [Commands](doc/commands.md)
- Cookbook
  - [Events](doc/cookbook/events.md)
  - [Aliases and blue/green reindex](doc/cookbook/aliases.md)
  - [Logging](doc/cookbook/logging.md)
  - [Manual providers](doc/cookbook/providers.md)
  - [Queue hooks](doc/cookbook/queues.md)

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

## License

This bundle is released under the [MIT License](LICENSE).
