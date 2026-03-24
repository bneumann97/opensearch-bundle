# Queue hooks

The bundle does not ship a queue implementation, but the persister and provider are small, composable services.

You can wrap them in a messenger handler or custom queue worker to perform async indexing.
