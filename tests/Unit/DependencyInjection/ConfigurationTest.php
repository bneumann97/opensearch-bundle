<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Tests\Unit\DependencyInjection;

use Bneumann\OpensearchBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
    public function testDefaultConfiguration(): void
    {
        $config = $this->process([[
            'clients' => [
                'default' => [
                    'hosts' => ['https://localhost:9200'],
                ],
            ],
        ]]);

        self::assertSame('default', $config['default_client']);
        self::assertArrayHasKey('default', $config['clients']);
        self::assertSame(['https://localhost:9200'], $config['clients']['default']['hosts']);
        self::assertTrue($config['clients']['default']['ssl_verification']);
        self::assertSame(1, $config['clients']['default']['retries']);
        self::assertNull($config['clients']['default']['username']);
        self::assertNull($config['clients']['default']['password']);
    }

    public function testNoClientsThrows(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('At least one client must be configured');

        $this->process([['clients' => []]]);
    }

    public function testInvalidDefaultClientThrows(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('must reference a configured client');

        $this->process([[
            'default_client' => 'nonexistent',
            'clients' => [
                'default' => ['hosts' => ['https://localhost:9200']],
            ],
        ]]);
    }

    public function testMultipleClients(): void
    {
        $config = $this->process([[
            'clients' => [
                'default' => ['hosts' => ['https://localhost:9200']],
                'analytics' => ['hosts' => ['https://analytics:9200'], 'retries' => 3],
            ],
        ]]);

        self::assertCount(2, $config['clients']);
        self::assertSame(3, $config['clients']['analytics']['retries']);
    }

    public function testIndexDefaults(): void
    {
        $config = $this->process([[
            'clients' => ['default' => ['hosts' => ['https://localhost:9200']]],
            'indexes' => [
                'products' => [],
            ],
        ]]);

        $index = $config['indexes']['products'];
        self::assertNull($index['client']);
        self::assertNull($index['index_name']);
        self::assertSame([], $index['settings']);
        self::assertSame([], $index['mappings']);
        self::assertSame([], $index['aliases']);
        self::assertFalse($index['serializer']['enabled']);
        self::assertNull($index['persistence']['driver']);
        self::assertSame('id', $index['persistence']['identifier']);
        self::assertSame('default', $index['persistence']['transformer']);
        self::assertSame('array', $index['finder']['hydration']);
        self::assertNull($index['repository']);
    }

    public function testIndexWithFullConfig(): void
    {
        $config = $this->process([[
            'clients' => ['default' => ['hosts' => ['https://localhost:9200']]],
            'indexes' => [
                'products' => [
                    'client' => 'default',
                    'index_name' => 'products_v2',
                    'settings' => ['number_of_shards' => 2],
                    'mappings' => ['properties' => ['title' => ['type' => 'text']]],
                    'aliases' => ['products_live' => []],
                    'serializer' => ['enabled' => true, 'groups' => ['search']],
                    'persistence' => [
                        'driver' => 'orm',
                        'model' => 'App\\Entity\\Product',
                        'provider' => true,
                        'listener' => true,
                        'identifier' => 'uuid',
                    ],
                    'finder' => ['hydration' => 'orm'],
                    'repository' => 'App\\Repository\\ProductSearchRepository',
                ],
            ],
        ]]);

        $index = $config['indexes']['products'];
        self::assertSame('products_v2', $index['index_name']);
        self::assertSame(2, $index['settings']['number_of_shards']);
        self::assertTrue($index['serializer']['enabled']);
        self::assertSame(['search'], $index['serializer']['groups']);
        self::assertSame('orm', $index['persistence']['driver']);
        self::assertTrue($index['persistence']['provider']);
        self::assertSame('uuid', $index['persistence']['identifier']);
        self::assertSame('orm', $index['finder']['hydration']);
    }

    public function testIndexTemplates(): void
    {
        $config = $this->process([[
            'clients' => ['default' => ['hosts' => ['https://localhost:9200']]],
            'index_templates' => [
                'logs' => [
                    'index_patterns' => ['logs-*'],
                    'settings' => ['number_of_replicas' => 0],
                ],
            ],
        ]]);

        self::assertArrayHasKey('logs', $config['index_templates']);
        self::assertSame(['logs-*'], $config['index_templates']['logs']['index_patterns']);
    }

    private function process(array $configs): array
    {
        return (new Processor())->processConfiguration(new Configuration(), $configs);
    }
}
