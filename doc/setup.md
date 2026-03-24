# Setup

## Installation

```
composer require bneumann/opensearch-bundle opensearch-project/opensearch-php
```

## Minimal configuration

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

## Console commands

- `opensearch:index:create`
- `opensearch:index:reset`
- `opensearch:index:populate`
- `opensearch:index:alias:switch`
- `opensearch:templates:reset`
- `opensearch:debug:config`
