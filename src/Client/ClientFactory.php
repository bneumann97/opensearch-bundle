<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Client;

use OpenSearch\Client;
use OpenSearch\ClientBuilder;
use Psr\Log\LoggerInterface;

final class ClientFactory
{
    public function create(array $config): Client
    {
        $builder = ClientBuilder::create();

        if (!empty($config['hosts'])) {
            $builder->setHosts($config['hosts']);
        }

        if (!empty($config['username'])) {
            $builder->setBasicAuthentication($config['username'], (string) ($config['password'] ?? ''));
        }

        if (array_key_exists('ssl_verification', $config)) {
            $builder->setSSLVerification((bool) $config['ssl_verification']);
        }

        if (isset($config['retries'])) {
            $builder->setRetries((int) $config['retries']);
        }

        $connectionParams = [];
        if (isset($config['connect_timeout'])) {
            $connectionParams['client']['timeout'] = (float) $config['connect_timeout'];
        }

        if (isset($config['request_timeout'])) {
            $connectionParams['client']['request_timeout'] = (float) $config['request_timeout'];
        }

        if (!empty($connectionParams)) {
            $builder->setConnectionParams($connectionParams);
        }

        if ($config['logger'] instanceof LoggerInterface) {
            $builder->setLogger($config['logger']);
        }

        return $builder->build();
    }
}
