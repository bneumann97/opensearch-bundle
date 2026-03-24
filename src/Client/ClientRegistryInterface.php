<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Client;

use OpenSearch\Client;

interface ClientRegistryInterface
{
    public function get(string $name): Client;

    public function getDefault(): Client;

    public function has(string $name): bool;
}
