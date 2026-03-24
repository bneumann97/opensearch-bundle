# Indexes

Each index can define settings, mappings, aliases, and persistence behavior.

```yaml
opensearch:
  indexes:
    products:
      index_name: 'products_%kernel.environment%'
      settings:
        number_of_shards: 1
      mappings:
        dynamic: false
        properties:
          name: { type: 'text' }
      aliases:
        products_read: {}
      persistence:
        driver: orm
        model: App\Entity\Product
        provider: true
        listener: true
        indexable: '@app.product_indexable'
```

## Indexable callback

`persistence.indexable` accepts:

- `@service_id` for a callable service
- `SomeClass::method` static callable
- `isIndexable` method name on the entity
