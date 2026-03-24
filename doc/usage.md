# Usage

## Finder services

Each index gets a finder service.

- `opensearch.finder.<index>` returns hydrated results
- `opensearch.finder_raw.<index>` returns raw OpenSearch response

Example service injection:

```php
public function __construct(FinderInterface $finder) {}
```

Example query:

```php
$results = $finder->find([
    'query' => [
        'match' => ['name' => 'bike'],
    ],
]);
```

## Repository pattern

Configure a repository per index:

```yaml
opensearch:
  indexes:
    products:
      repository: App\Search\ProductRepository
```

Repository base class:

```php
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
