<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Command;

use Bneumann\OpensearchBundle\Client\ClientRegistryInterface;
use Bneumann\OpensearchBundle\Index\IndexDefinition;
use Bneumann\OpensearchBundle\Index\IndexManagerInterface;
use Bneumann\OpensearchBundle\Index\IndexNameGenerator;
use Bneumann\OpensearchBundle\Index\IndexRegistry;
use Bneumann\OpensearchBundle\Provider\ProviderRegistry;
use Bneumann\OpensearchBundle\Persister\PersisterRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'opensearch:index:alias:switch', description: 'Create new index, populate, and atomically switch alias.')]
final class AliasSwitchCommand extends Command
{
    public function __construct(
        private readonly IndexRegistry $indexRegistry,
        private readonly IndexManagerInterface $indexManager,
        private readonly IndexNameGenerator $nameGenerator,
        private readonly ClientRegistryInterface $clients,
        private readonly ProviderRegistry $providers,
        private readonly PersisterRegistry $persisters,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('index', InputArgument::REQUIRED, 'Index name as configured')
            ->addArgument('alias', InputArgument::REQUIRED, 'Alias to switch')
            ->addOption('populate', null, InputOption::VALUE_NONE, 'Populate new index before alias switch')
            ->addOption('delete-old', null, InputOption::VALUE_NONE, 'Delete old indices bound to alias');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $index = $this->indexRegistry->get((string) $input->getArgument('index'));
        $alias = (string) $input->getArgument('alias');
        $newIndexName = $this->nameGenerator->generatePhysicalName($index->getIndexName());

        $newIndex = new IndexDefinition(
            $index->getName(),
            $newIndexName,
            $index->getClient(),
            $index->getSettings(),
            $index->getMappings(),
            [],
            $index->getSerializerConfig(),
            $index->getPersistenceConfig(),
            $index->getFinderConfig(),
            $index->getRepositoryClass(),
        );

        $this->indexManager->create($newIndex, true);

        if ((bool) $input->getOption('populate') && $this->providers->has($index->getName())) {
            $provider = $this->providers->get($index->getName());
            $this->persisters->get($index->getName())->insertMany($newIndex, $provider->provide($newIndex, 200));
        }

        $client = $this->clients->get($index->getClient());
        $existing = $client->indices()->getAlias(['name' => $alias]);

        $actions = [];
        foreach (array_keys($existing) as $oldIndex) {
            $actions[] = ['remove' => ['index' => $oldIndex, 'alias' => $alias]];
        }

        $actions[] = ['add' => ['index' => $newIndexName, 'alias' => $alias]];

        $client->indices()->updateAliases(['body' => ['actions' => $actions]]);

        if ((bool) $input->getOption('delete-old')) {
            foreach (array_keys($existing) as $oldIndex) {
                $client->indices()->delete(['index' => $oldIndex]);
            }
        }

        $output->writeln(sprintf('Alias "%s" switched to "%s".', $alias, $newIndexName));

        return Command::SUCCESS;
    }
}
