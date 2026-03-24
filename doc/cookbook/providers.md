# Manual providers

Implement `ProviderInterface` to supply objects for the populate command.

```php
final class ProductProvider implements ProviderInterface
{
    public function provide(IndexDefinition $index, int $batchSize): iterable
    {
        yield from $this->repository->iterateForSearch();
    }
}
```

Register the provider and reference it from your index configuration.
