<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('opensearch');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('default_client')->defaultValue('default')->end()
                ->arrayNode('clients')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->arrayNode('hosts')
                                ->scalarPrototype()->end()
                                ->defaultValue(['https://localhost:9200'])
                            ->end()
                            ->scalarNode('username')->defaultNull()->end()
                            ->scalarNode('password')->defaultNull()->end()
                            ->booleanNode('ssl_verification')->defaultTrue()->end()
                            ->integerNode('retries')->defaultValue(1)->min(0)->end()
                            ->scalarNode('logger')->defaultNull()->end()
                            ->floatNode('connect_timeout')->defaultNull()->end()
                            ->floatNode('request_timeout')->defaultNull()->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('indexes')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('client')->defaultNull()->end()
                            ->scalarNode('index_name')->defaultNull()->end()
                            ->arrayNode('settings')->normalizeKeys(false)->defaultValue([])->variablePrototype()->end()->end()
                            ->arrayNode('mappings')->normalizeKeys(false)->defaultValue([])->variablePrototype()->end()->end()
                            ->arrayNode('aliases')->normalizeKeys(false)->defaultValue([])->variablePrototype()->end()->end()
                            ->arrayNode('serializer')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->booleanNode('enabled')->defaultFalse()->end()
                                    ->arrayNode('groups')->scalarPrototype()->end()->defaultValue([])->end()
                                    ->arrayNode('context')->normalizeKeys(false)->defaultValue([])->variablePrototype()->end()->end()
                                ->end()
                            ->end()
                            ->arrayNode('persistence')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('driver')->defaultNull()->end()
                                    ->scalarNode('model')->defaultNull()->end()
                                    ->booleanNode('provider')->defaultFalse()->end()
                                    ->booleanNode('listener')->defaultFalse()->end()
                                    ->scalarNode('identifier')->defaultValue('id')->end()
                                    ->scalarNode('transformer')->defaultValue('default')->end()
                                    ->scalarNode('indexable')->defaultNull()->end()
                                ->end()
                            ->end()
                            ->arrayNode('finder')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('service')->defaultValue('default')->end()
                                    ->scalarNode('hydration')->defaultValue('array')->end()
                                    ->scalarNode('hydrator')->defaultNull()->end()
                                ->end()
                            ->end()
                            ->scalarNode('repository')->defaultNull()->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('index_templates')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->arrayNode('index_patterns')->scalarPrototype()->end()->defaultValue([])->end()
                            ->scalarNode('template_name')->defaultNull()->end()
                            ->arrayNode('settings')->normalizeKeys(false)->defaultValue([])->variablePrototype()->end()->end()
                            ->arrayNode('mappings')->normalizeKeys(false)->defaultValue([])->variablePrototype()->end()->end()
                            ->arrayNode('aliases')->normalizeKeys(false)->defaultValue([])->variablePrototype()->end()->end()
                            ->scalarNode('client')->defaultNull()->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        $this->addValidation($rootNode);

        return $treeBuilder;
    }

    private function addValidation(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->validate()
                ->ifTrue(function (array $v): bool {
                    return empty($v['clients']);
                })
                ->thenInvalid('At least one client must be configured under "opensearch.clients".')
            ->end();

        $rootNode
            ->validate()
                ->ifTrue(function (array $v): bool {
                    return !isset($v['clients'][$v['default_client'] ?? '']);
                })
                ->thenInvalid('The "opensearch.default_client" must reference a configured client.')
            ->end();
    }
}
