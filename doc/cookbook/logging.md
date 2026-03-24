# Logging

If Monolog is installed, you can pass a logger service to the client.

```yaml
opensearch:
  clients:
    default:
      hosts: ['https://localhost:9200']
      logger: 'monolog.logger.opensearch'
```
