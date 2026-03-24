<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Tests\Integration;

use Bneumann\OpensearchBundle\OpensearchBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

final class TestKernel extends Kernel
{
    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new OpensearchBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(function (ContainerBuilder $container): void {
            $container->loadFromExtension('framework', [
                'secret' => 'test',
                'test' => true,
            ]);

            $container->loadFromExtension('opensearch', [
                'clients' => [
                    'default' => [
                        'hosts' => ['https://localhost:9200'],
                    ],
                ],
                'indexes' => [
                    'products' => [
                        'index_name' => 'products_test',
                        'settings' => ['number_of_shards' => 1],
                        'mappings' => [
                            'properties' => [
                                'name' => ['type' => 'text'],
                                'price' => ['type' => 'float'],
                            ],
                        ],
                    ],
                ],
            ]);
        });
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/opensearch_bundle_test/cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/opensearch_bundle_test/log';
    }
}
