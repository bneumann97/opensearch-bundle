# Serializer

Enable the Symfony Serializer to normalize entities to arrays.

```yaml
opensearch:
  indexes:
    products:
      serializer:
        enabled: true
        groups: ['search']
```

The bundle uses `SerializerInterface::normalize()` with `json` format and the configured context.
