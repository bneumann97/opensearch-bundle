# Commands

The bundle provides six console commands for managing OpenSearch indexes and templates.

## opensearch:index:create

Create an OpenSearch index from its configured mappings and settings.

```bash
bin/console opensearch:index:create <index>
```

| Argument/Option | Description |
|---|---|
| `index` | Index name as configured (required) |
| `--force` | Delete the existing index first, then recreate it |

**Examples:**

```bash
# Create the products index
bin/console opensearch:index:create products

# Recreate the products index (deletes existing data)
bin/console opensearch:index:create products --force
```

## opensearch:index:reset

Delete and recreate an OpenSearch index. Equivalent to `create --force`.

```bash
bin/console opensearch:index:reset <index>
```

| Argument/Option | Description |
|---|---|
| `index` | Index name as configured (required) |

**Examples:**

```bash
bin/console opensearch:index:reset products
```

## opensearch:index:populate

Populate an OpenSearch index by fetching objects from the configured provider and bulk-indexing them.

```bash
bin/console opensearch:index:populate <index> [--reset] [--batch-size=100] [--sleep=0]
```

| Argument/Option | Description |
|---|---|
| `index` | Index name as configured (required) |
| `--reset` | Reset the index before populating |
| `--batch-size` | Number of objects per bulk request (default: 100) |
| `--sleep` | Milliseconds to sleep between batches (default: 0) |

The index must have a provider configured (e.g. `persistence.provider: true` for Doctrine ORM).

**Examples:**

```bash
# Populate with defaults
bin/console opensearch:index:populate products

# Reset first, then populate in batches of 500
bin/console opensearch:index:populate products --reset --batch-size=500

# Throttle to avoid overloading the cluster
bin/console opensearch:index:populate products --batch-size=200 --sleep=50
```

## opensearch:index:alias:switch

Create a new timestamped index, optionally populate it, and atomically switch an alias to point to it. This enables zero-downtime reindexing (blue/green deployment pattern).

```bash
bin/console opensearch:index:alias:switch <index> <alias> [--populate] [--delete-old]
```

| Argument/Option | Description |
|---|---|
| `index` | Index name as configured (required) |
| `alias` | Alias name to switch (required) |
| `--populate` | Populate the new index before switching |
| `--delete-old` | Delete old indices that were bound to the alias |

**Examples:**

```bash
# Create new index, populate, switch alias, and clean up
bin/console opensearch:index:alias:switch products products_live --populate --delete-old

# Switch alias without repopulating (useful if data is already in the new index)
bin/console opensearch:index:alias:switch products products_live
```

See [Aliases cookbook](cookbook/aliases.md) for more details on blue/green reindexing.

## opensearch:templates:reset

Reset all configured index templates. Iterates through every template defined in the bundle configuration and applies it to OpenSearch.

```bash
bin/console opensearch:templates:reset
```

This command takes no arguments or options.

**Examples:**

```bash
bin/console opensearch:templates:reset
```

See [Templates](templates.md) for template configuration.

## opensearch:debug:config

Print the fully resolved OpenSearch bundle configuration as JSON. Useful for debugging merged configuration from multiple files or environments.

```bash
bin/console opensearch:debug:config
```

This command takes no arguments or options.

**Examples:**

```bash
bin/console opensearch:debug:config

# Pipe to jq for filtering
bin/console opensearch:debug:config | jq '.indexes.products'
```
