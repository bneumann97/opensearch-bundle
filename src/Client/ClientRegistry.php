<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Client;

use OpenSearch\Client;
use Bneumann\OpensearchBundle\Exception\ClientException;

final class ClientRegistry implements ClientRegistryInterface
{
    /** @var array<string, Client> */
    private array $clients;

    public function __construct(array $clients, private readonly string $defaultClient)
    {
        $this->clients = $clients;
    }

    public function get(string $name): Client
    {
        if (!$this->has($name)) {
            throw new ClientException(sprintf('OpenSearch client "%s" is not configured.', $name));
        }

        return $this->clients[$name];
    }

    public function getDefault(): Client
    {
        return $this->get($this->defaultClient);
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->clients);
    }
}
