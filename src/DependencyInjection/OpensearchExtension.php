<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\DependencyInjection;

use Bneumann\OpensearchBundle\Client\ClientFactory;
use Bneumann\OpensearchBundle\Client\ClientRegistry;
use Bneumann\OpensearchBundle\Command\AliasSwitchCommand;
use Bneumann\OpensearchBundle\Command\CreateIndexCommand;
use Bneumann\OpensearchBundle\Command\DebugConfigCommand;
use Bneumann\OpensearchBundle\Command\PopulateIndexCommand;
use Bneumann\OpensearchBundle\Command\ResetIndexCommand;
use Bneumann\OpensearchBundle\Command\ResetTemplatesCommand;
use Bneumann\OpensearchBundle\Doctrine\Hydrator\OrmHydrator;
use Bneumann\OpensearchBundle\Doctrine\Listener\OrmIndexConfig;
use Bneumann\OpensearchBundle\Doctrine\Provider\OrmProvider;
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
use Bneumann\OpensearchBundle\Template\TemplateManager;
use Bneumann\OpensearchBundle\Transformer\DefaultTransformer;
use Bneumann\OpensearchBundle\Transformer\SerializerTransformer;
use OpenSearch\Client;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Serializer\SerializerInterface;

final class OpensearchExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.php');

        if ($this->isDoctrineBundleEnabled($container)) {
            $loader->load('services_doctrine.php');
        }

        $container->setParameter('opensearch.config', $config);

        $this->registerClients($container, $config);
        $this->registerIndexes($container, $config);
        $this->registerTemplates($container, $config);
        $this->registerCommands($container, $config);
    }

    private function registerClients(ContainerBuilder $container, array $config): void
    {
        $clientRefs = [];
        foreach ($config['clients'] as $name => $clientConfig) {
            if (!empty($clientConfig['logger'])) {
                $clientConfig['logger'] = new Reference($clientConfig['logger']);
            }

            $def = new Definition(Client::class);
            $def->setFactory([new Reference(ClientFactory::class), 'create']);
            $def->setArguments([$clientConfig]);
            $def->setPublic(false);

            $container->setDefinition('opensearch.client.' . $name, $def);
            $clientRefs[$name] = new Reference('opensearch.client.' . $name);
        }

        $container->getDefinition(ClientRegistry::class)->setArguments([$clientRefs, $config['default_client']]);

        $container->setAlias(Client::class, 'opensearch.client.' . $config['default_client']);
    }

    private function registerIndexes(ContainerBuilder $container, array $config): void
    {
        $indexRefs = [];
        $providerRefs = [];
        $persisterRefs = [];
        $repositoryRefs = [];
        $repositoryIds = [];
        $ormConfigs = [];

        foreach ($config['indexes'] as $name => $indexConfig) {
            $indexConfig['client'] = $indexConfig['client'] ?? $config['default_client'];
            $indexConfig['index_name'] = $indexConfig['index_name'] ?? $name;

            $indexDefinition = new Definition(IndexDefinition::class, [
                $name,
                $indexConfig['index_name'],
                $indexConfig['client'],
                $indexConfig['settings'],
                $indexConfig['mappings'],
                $indexConfig['aliases'],
                $indexConfig['serializer'],
                $indexConfig['persistence'],
                $indexConfig['finder'],
                $indexConfig['repository'],
            ]);
            $indexDefinition->setPublic(false);

            $container->setDefinition('opensearch.index.' . $name, $indexDefinition);
            $indexRefs[$name] = new Reference('opensearch.index.' . $name);

            $transformerRef = $indexConfig['serializer']['enabled'] && interface_exists(SerializerInterface::class)
                ? new Reference(SerializerTransformer::class)
                : new Reference(DefaultTransformer::class);

            $persister = new Definition(Persister::class, [
                new Reference(ClientRegistry::class),
                $transformerRef,
                new Reference('event_dispatcher'),
            ]);
            $persister->setPublic(false);

            $container->setDefinition('opensearch.persister.' . $name, $persister);
            $persisterRefs[$name] = new Reference('opensearch.persister.' . $name);

            $rawFinder = new Definition(RawFinder::class, [
                new Reference(ClientRegistry::class),
                new Reference('opensearch.index.' . $name),
                new Reference('event_dispatcher'),
            ]);
            $rawFinder->setPublic(false);
            $container->setDefinition('opensearch.finder_raw.' . $name, $rawFinder);

            $hydratorRef = $this->resolveHydrator($container, $name, $indexConfig);

            $mappedFinder = new Definition(MappedFinder::class, [
                new Reference(ClientRegistry::class),
                new Reference('opensearch.index.' . $name),
                $hydratorRef,
                new Reference('event_dispatcher'),
            ]);
            $mappedFinder->setPublic(false);
            $container->setDefinition('opensearch.finder.' . $name, $mappedFinder);

            if (!empty($indexConfig['repository'])) {
                $repositoryId = 'opensearch.repository.' . $name;
                $repository = $indexConfig['repository'];

                if (str_starts_with($repository, '@')) {
                    $repositoryId = substr($repository, 1);
                } else {
                    $repositoryDef = new Definition($repository, [new Reference('opensearch.finder.' . $name)]);
                    $repositoryDef->setPublic(false);
                    $container->setDefinition($repositoryId, $repositoryDef);
                }

                $repositoryRefs[$name] = new Reference($repositoryId);
                $repositoryIds[$name] = $repositoryId;
            } else {
                $repositoryId = 'opensearch.repository.' . $name;
                $repositoryDef = new Definition(DefaultRepository::class, [new Reference('opensearch.finder.' . $name)]);
                $repositoryDef->setPublic(false);
                $container->setDefinition($repositoryId, $repositoryDef);
                $repositoryRefs[$name] = new Reference($repositoryId);
                $repositoryIds[$name] = $repositoryId;
            }

            if (($indexConfig['persistence']['provider'] ?? false) && ($indexConfig['persistence']['driver'] ?? null) === 'orm') {
                if (!$this->isDoctrineBundleEnabled($container)) {
                    throw new \InvalidArgumentException(sprintf('Index "%s" uses ORM provider but DoctrineBundle is not enabled.', $name));
                }

                $providerDef = new Definition(OrmProvider::class, [
                    new Reference('doctrine'),
                    $indexConfig['persistence']['model'],
                ]);
                $providerDef->setPublic(false);
                $providerId = 'opensearch.provider.' . $name;
                $container->setDefinition($providerId, $providerDef);
                $providerRefs[$name] = new Reference($providerId);
            }

            if (($indexConfig['persistence']['listener'] ?? false) && ($indexConfig['persistence']['driver'] ?? null) === 'orm') {
                $ormConfigs[] = new Definition(OrmIndexConfig::class, [
                    new Reference('opensearch.index.' . $name),
                    new Reference('opensearch.persister.' . $name),
                    $indexConfig['persistence']['model'],
                    $indexConfig['persistence']['identifier'],
                    $indexConfig['persistence']['indexable'],
                ]);
            }
        }

        $container->getDefinition(IndexRegistry::class)->setArgument(0, $indexRefs);
        $container->getDefinition(ProviderRegistry::class)->setArgument(0, $providerRefs);
        $container->getDefinition(PersisterRegistry::class)->setArgument(0, $persisterRefs);

        $repositoryLocator = new Definition(ServiceLocator::class, [new ServiceLocatorArgument($repositoryRefs)]);
        $container->setDefinition('opensearch.repository_locator', $repositoryLocator);
        $container->getDefinition(RepositoryManager::class)->setArguments([new Reference('opensearch.repository_locator'), $repositoryIds]);

        if (!empty($ormConfigs) && $container->hasDefinition('opensearch.orm_index_listener')) {
            $container->getDefinition('opensearch.orm_index_listener')->setArgument(0, $ormConfigs);
        }
    }

    private function registerTemplates(ContainerBuilder $container, array $config): void
    {
        $templates = [];
        foreach ($config['index_templates'] as $name => $template) {
            $template['client'] = $template['client'] ?? $config['default_client'];
            $template['template_name'] = $template['template_name'] ?? $name;
            $templates[] = $template;
        }

        $container->setParameter('opensearch.templates', $templates);
    }

    private function registerCommands(ContainerBuilder $container, array $config): void
    {
        $container->register(CreateIndexCommand::class)
            ->setArguments([new Reference(IndexRegistry::class), new Reference(\Bneumann\OpensearchBundle\Index\IndexManagerInterface::class)])
            ->addTag('console.command');

        $container->register(ResetIndexCommand::class)
            ->setArguments([new Reference(IndexRegistry::class), new Reference(\Bneumann\OpensearchBundle\Index\IndexManagerInterface::class)])
            ->addTag('console.command');

        $container->register(PopulateIndexCommand::class)
            ->setArguments([
                new Reference(IndexRegistry::class),
                new Reference(\Bneumann\OpensearchBundle\Index\IndexManagerInterface::class),
                new Reference(ProviderRegistry::class),
                new Reference(\Bneumann\OpensearchBundle\Persister\PersisterRegistry::class),
                new Reference('event_dispatcher'),
            ])
            ->addTag('console.command');

        $container->register(AliasSwitchCommand::class)
            ->setArguments([
                new Reference(IndexRegistry::class),
                new Reference(\Bneumann\OpensearchBundle\Index\IndexManagerInterface::class),
                new Reference(\Bneumann\OpensearchBundle\Index\IndexNameGenerator::class),
                new Reference(\Bneumann\OpensearchBundle\Client\ClientRegistryInterface::class),
                new Reference(ProviderRegistry::class),
                new Reference(\Bneumann\OpensearchBundle\Persister\PersisterRegistry::class),
            ])
            ->addTag('console.command');

        $container->register(ResetTemplatesCommand::class)
            ->setArguments([
                new Reference(TemplateManager::class),
                '%opensearch.templates%',
            ])
            ->addTag('console.command');

        $container->register(DebugConfigCommand::class)
            ->setArguments(['%opensearch.config%'])
            ->addTag('console.command');
    }

    private function resolveHydrator(ContainerBuilder $container, string $name, array $indexConfig): Reference
    {
        if (!empty($indexConfig['finder']['hydrator'])) {
            return new Reference($indexConfig['finder']['hydrator']);
        }

        if (($indexConfig['finder']['hydration'] ?? 'array') === 'orm') {
            if (!$this->isDoctrineBundleEnabled($container)) {
                throw new \InvalidArgumentException(sprintf('Index "%s" uses ORM hydration but DoctrineBundle is not enabled.', $name));
            }

            $hydratorId = 'opensearch.hydrator.orm.' . $name;
            $container->setDefinition($hydratorId, new Definition(OrmHydrator::class, [
                new Reference('doctrine'),
                $indexConfig['persistence']['model'],
                $indexConfig['persistence']['identifier'],
            ]));

            return new Reference($hydratorId);
        }

        return new Reference(ArrayHydrator::class);
    }

    private function isDoctrineBundleEnabled(ContainerBuilder $container): bool
    {
        if ($container->hasExtension('doctrine')) {
            return true;
        }

        if (!$container->hasParameter('kernel.bundles')) {
            return false;
        }

        $bundles = $container->getParameter('kernel.bundles');
        if (!is_array($bundles)) {
            return false;
        }

        return isset($bundles['DoctrineBundle']);
    }
}
