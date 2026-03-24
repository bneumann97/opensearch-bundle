<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Tests\Integration;

use Bneumann\OpensearchBundle\Client\ClientRegistry;
use Bneumann\OpensearchBundle\Client\ClientRegistryInterface;
use Bneumann\OpensearchBundle\Index\IndexManager;
use Bneumann\OpensearchBundle\Index\IndexManagerInterface;
use Bneumann\OpensearchBundle\Index\IndexNameGenerator;
use Bneumann\OpensearchBundle\Index\IndexRegistry;
use Bneumann\OpensearchBundle\Persister\PersisterRegistry;
use Bneumann\OpensearchBundle\Template\TemplateManager;
use Bneumann\OpensearchBundle\Template\TemplateManagerInterface;
use Bneumann\OpensearchBundle\Transformer\DefaultTransformer;
use Bneumann\OpensearchBundle\Transformer\TransformerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class KernelTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    public function testContainerBuilds(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        self::assertTrue($container->has(IndexRegistry::class));
    }

    public function testServiceAliases(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        self::assertInstanceOf(ClientRegistry::class, $container->get(ClientRegistryInterface::class));
        self::assertInstanceOf(IndexManager::class, $container->get(IndexManagerInterface::class));
        self::assertInstanceOf(TemplateManager::class, $container->get(TemplateManagerInterface::class));
        self::assertInstanceOf(DefaultTransformer::class, $container->get(TransformerInterface::class));
    }

    public function testIndexRegistryContainsConfiguredIndex(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $registry = $container->get(IndexRegistry::class);
        $index = $registry->get('products');

        self::assertSame('products', $index->getName());
        self::assertSame('products_test', $index->getIndexName());
    }

    public function testPersisterRegistryHasConfiguredIndex(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $registry = $container->get(PersisterRegistry::class);

        self::assertTrue($registry->has('products'));
    }

    public function testIndexNameGeneratorIsAvailable(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        self::assertInstanceOf(IndexNameGenerator::class, $container->get(IndexNameGenerator::class));
    }
}
