<?php

declare(strict_types=1);

use Bneumann\OpensearchBundle\Doctrine\Listener\IndexableChecker;
use Bneumann\OpensearchBundle\Doctrine\Listener\OrmIndexListener;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();
    $services->defaults()->autowire()->autoconfigure();

    $services->set(IndexableChecker::class)
        ->args([service('service_container')]);

    $services->set('opensearch.orm_index_listener', OrmIndexListener::class)
        ->args([[], service(IndexableChecker::class)])
        ->tag('doctrine.event_subscriber');
};
