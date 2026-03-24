<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Tests\Unit\DependencyInjection;

use Bneumann\OpensearchBundle\Client\ClientRegistry;
use Bneumann\OpensearchBundle\DependencyInjection\OpensearchExtension;
use Bneumann\OpensearchBundle\Finder\ArrayHydrator;
use Bneumann\OpensearchBundle\Finder\MappedFinder;
use Bneumann\OpensearchBundle\Finder\RawFinder;
use Bneumann\OpensearchBundle\Index\IndexDefinition;
use Bneumann\OpensearchBundle\Index\IndexRegistry;
use Bneumann\OpensearchBundle\Persister\Persister;
use Bneumann\OpensearchBundle\Persister\PersisterRegistry;
use Bneumann\OpensearchBundle\Provider\ProviderRegistry;
use Bneumann\OpensearchBundle\Repository\DefaultRepository;
use Bneumann\OpensearchBundle\Repository\RepositoryManager;
use Bneumann\OpensearchBundle\Command\CreateIndexCommand;
use Bneumann\OpensearchBundle\Command\ResetIndexCommand;
use Bneumann\OpensearchBundle\Command\PopulateIndexCommand;
use Bneumann\OpensearchBundle\Command\DebugConfigCommand;
use Bneumann\OpensearchBundle\Command\ResetTemplatesCommand;
use Bneumann\OpensearchBundle\Command\AliasSwitchCommand;
use OpenSearch\Client;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class OpensearchExtensionTest extends TestCase
{
    public function testClientRegistration(): void
    {
        $container = $this->buildContainer([
            'clients' => [
                'default' => ['hosts' => ['https://localhost:9200']],
                'analytics' => ['hosts' => ['https://analytics:9200']],
            ],
        ]);

        self::assertTrue($container->hasDefinition('opensearch.client.default'));
        self::assertTrue($container->hasDefinition('opensearch.client.analytics'));
        self::assertTrue($container->hasAlias(Client::class));
    }

    public function testIndexRegistration(): void
    {
        $container = $this->buildContainer([
            'clients' => ['default' => ['hosts' => ['https://localhost:9200']]],
            'indexes' => [
                'products' => ['index_name' => 'products_v1'],
            ],
        ]);

        self::assertTrue($container->hasDefinition('opensearch.index.products'));
        self::assertTrue($container->hasDefinition('opensearch.persister.products'));
        self::assertTrue($container->hasDefinition('opensearch.finder_raw.products'));
        self::assertTrue($container->hasDefinition('opensearch.finder.products'));
        self::assertTrue($container->hasDefinition('opensearch.repository.products'));
    }

    public function testMultipleIndexes(): void
    {
        $container = $this->buildContainer([
            'clients' => ['default' => ['hosts' => ['https://localhost:9200']]],
            'indexes' => [
                'products' => ['index_name' => 'products_v1'],
                'orders' => ['index_name' => 'orders_v1'],
            ],
        ]);

        self::assertTrue($container->hasDefinition('opensearch.index.products'));
        self::assertTrue($container->hasDefinition('opensearch.index.orders'));
        self::assertTrue($container->hasDefinition('opensearch.persister.products'));
        self::assertTrue($container->hasDefinition('opensearch.persister.orders'));
    }

    public function testCommandsAreRegistered(): void
    {
        $container = $this->buildContainer([
            'clients' => ['default' => ['hosts' => ['https://localhost:9200']]],
        ]);

        self::assertTrue($container->hasDefinition(CreateIndexCommand::class));
        self::assertTrue($container->hasDefinition(ResetIndexCommand::class));
        self::assertTrue($container->hasDefinition(PopulateIndexCommand::class));
        self::assertTrue($container->hasDefinition(DebugConfigCommand::class));
        self::assertTrue($container->hasDefinition(ResetTemplatesCommand::class));
        self::assertTrue($container->hasDefinition(AliasSwitchCommand::class));
    }

    public function testCommandsAreTaggedAsConsoleCommand(): void
    {
        $container = $this->buildContainer([
            'clients' => ['default' => ['hosts' => ['https://localhost:9200']]],
        ]);

        self::assertTrue($container->getDefinition(CreateIndexCommand::class)->hasTag('console.command'));
        self::assertTrue($container->getDefinition(PopulateIndexCommand::class)->hasTag('console.command'));
    }

    public function testTemplatesAreRegistered(): void
    {
        $container = $this->buildContainer([
            'clients' => ['default' => ['hosts' => ['https://localhost:9200']]],
            'index_templates' => [
                'logs' => [
                    'index_patterns' => ['logs-*'],
                    'settings' => ['number_of_replicas' => 0],
                ],
            ],
        ]);

        $templates = $container->getParameter('opensearch.templates');
        self::assertCount(1, $templates);
        self::assertSame('default', $templates[0]['client']);
        self::assertSame('logs', $templates[0]['template_name']);
    }

    public function testCustomRepositoryClass(): void
    {
        $container = $this->buildContainer([
            'clients' => ['default' => ['hosts' => ['https://localhost:9200']]],
            'indexes' => [
                'products' => [
                    'index_name' => 'products_v1',
                    'repository' => 'App\\Repository\\ProductSearchRepository',
                ],
            ],
        ]);

        $def = $container->getDefinition('opensearch.repository.products');
        self::assertSame('App\\Repository\\ProductSearchRepository', $def->getClass());
    }

    public function testServiceReferenceRepository(): void
    {
        $container = $this->buildContainer([
            'clients' => ['default' => ['hosts' => ['https://localhost:9200']]],
            'indexes' => [
                'products' => [
                    'index_name' => 'products_v1',
                    'repository' => '@app.product_repo',
                ],
            ],
        ]);

        // When using @service reference, the service ID is used directly
        self::assertFalse($container->hasDefinition('opensearch.repository.products'));
    }

    public function testIndexNameDefaultsToKeyName(): void
    {
        $container = $this->buildContainer([
            'clients' => ['default' => ['hosts' => ['https://localhost:9200']]],
            'indexes' => [
                'products' => [],
            ],
        ]);

        $def = $container->getDefinition('opensearch.index.products');
        $args = $def->getArguments();
        // Second argument is index_name, defaults to key name 'products'
        self::assertSame('products', $args[1]);
    }

    private function buildContainer(array $config): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $extension = new OpensearchExtension();
        $extension->load([$config], $container);

        return $container;
    }
}
