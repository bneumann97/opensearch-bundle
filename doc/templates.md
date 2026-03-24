# Templates

Define index templates and reset them via `opensearch:templates:reset`.

```yaml
opensearch:
  index_templates:
    products_template:
      index_patterns: ['products_*']
      template_name: 'products_template'
      settings:
        number_of_shards: 1
```
