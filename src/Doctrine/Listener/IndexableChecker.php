<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Doctrine\Listener;

use Psr\Container\ContainerInterface;

final class IndexableChecker
{
    public function __construct(private readonly ContainerInterface $container)
    {
    }

    public function isIndexable(object $entity, ?string $indexable): bool
    {
        if ($indexable === null) {
            return true;
        }

        if (str_starts_with($indexable, '@')) {
            $serviceId = substr($indexable, 1);
            $callable = $this->container->get($serviceId);

            return (bool) $callable($entity);
        }

        if (str_contains($indexable, '::')) {
            return (bool) call_user_func($indexable, $entity);
        }

        if (method_exists($entity, $indexable)) {
            return (bool) $entity->{$indexable}();
        }

        if (function_exists($indexable)) {
            return (bool) $indexable($entity);
        }

        return true;
    }
}
