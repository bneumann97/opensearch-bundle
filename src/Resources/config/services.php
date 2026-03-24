<?php

declare(strict_types=1);

use Bneumann\OpensearchBundle\Client\ClientFactory;
use Bneumann\OpensearchBundle\Client\ClientRegistry;
use Bneumann\OpensearchBundle\Client\ClientRegistryInterface;
use Bneumann\OpensearchBundle\Finder\ArrayHydrator;
use Bneumann\OpensearchBundle\Finder\HydratorInterface;
use Bneumann\OpensearchBundle\Index\IndexManager;
use Bneumann\OpensearchBundle\Index\IndexManagerInterface;
use Bneumann\OpensearchBundle\Index\IndexNameGenerator;
use Bneumann\OpensearchBundle\Index\IndexRegistry;
use Bneumann\OpensearchBundle\Persister\Persister;
use Bneumann\OpensearchBundle\Persister\PersisterInterface;
use Bneumann\OpensearchBundle\Persister\PersisterRegistry;
use Bneumann\OpensearchBundle\Provider\ProviderRegistry;
use Bneumann\OpensearchBundle\Repository\RepositoryManager;
use Bneumann\OpensearchBundle\Repository\RepositoryManagerInterface;
use Bneumann\OpensearchBundle\Template\TemplateManager;
use Bneumann\OpensearchBundle\Template\TemplateManagerInterface;
use Bneumann\OpensearchBundle\Transformer\DefaultTransformer;
use Bneumann\OpensearchBundle\Transformer\SerializerTransformer;
use Bneumann\OpensearchBundle\Transformer\TransformerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();
    $services->defaults()->autowire()->autoconfigure();

    $services->set(ClientFactory::class);

    $services->set(ClientRegistry::class)
        ->args([[], 'default']);
    $services->alias(ClientRegistryInterface::class, ClientRegistry::class);

    $services->set(IndexRegistry::class)
        ->args([[]]);

    $services->set(IndexManager::class)
        ->args([service(ClientRegistry::class), service('event_dispatcher')]);
    $services->alias(IndexManagerInterface::class, IndexManager::class);

    $services->set(TemplateManager::class)
        ->args([service(ClientRegistry::class), service('event_dispatcher')]);
    $services->alias(TemplateManagerInterface::class, TemplateManager::class);

    $services->set(DefaultTransformer::class);
    $services->set(SerializerTransformer::class);
    $services->alias(TransformerInterface::class, DefaultTransformer::class);

    $services->set(ArrayHydrator::class);
    $services->alias(HydratorInterface::class, ArrayHydrator::class);

    $services->set(Persister::class)
        ->args([service(ClientRegistry::class), service(DefaultTransformer::class), service('event_dispatcher')]);
    $services->alias(PersisterInterface::class, Persister::class);

    $services->set(ProviderRegistry::class)
        ->args([[]]);

    $services->set(PersisterRegistry::class)
        ->args([[]]);

    $services->set(RepositoryManager::class)
        ->args([service('service_locator'), []]);
    $services->alias(RepositoryManagerInterface::class, RepositoryManager::class);

    $services->set(IndexNameGenerator::class);
};
