# Aliases and blue/green reindex

Use `opensearch:index:alias:switch` to create a new physical index and atomically switch an alias.

Example:

```
php bin/console opensearch:index:alias:switch products products_read --populate --delete-old
```
