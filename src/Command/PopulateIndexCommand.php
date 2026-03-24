<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Command;

use Bneumann\OpensearchBundle\Event\BatchProcessedEvent;
use Bneumann\OpensearchBundle\Event\PostPopulateEvent;
use Bneumann\OpensearchBundle\Event\PrePopulateEvent;
use Bneumann\OpensearchBundle\Index\IndexManagerInterface;
use Bneumann\OpensearchBundle\Index\IndexRegistry;
use Bneumann\OpensearchBundle\Persister\PersisterRegistry;
use Bneumann\OpensearchBundle\Provider\ProviderRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsCommand(name: 'opensearch:index:populate', description: 'Populate an OpenSearch index from a provider.')]
final class PopulateIndexCommand extends Command
{
    public function __construct(
        private readonly IndexRegistry $indexRegistry,
        private readonly IndexManagerInterface $indexManager,
        private readonly ProviderRegistry $providers,
        private readonly PersisterRegistry $persisters,
        private readonly ?EventDispatcherInterface $dispatcher = null,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('index', InputArgument::REQUIRED, 'Index name as configured')
            ->addOption('reset', null, InputOption::VALUE_NONE, 'Reset index before populating')
            ->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Batch size for bulk indexing', 100)
            ->addOption('sleep', null, InputOption::VALUE_REQUIRED, 'Sleep between batches in milliseconds', 0);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $index = $this->indexRegistry->get((string) $input->getArgument('index'));

        if ((bool) $input->getOption('reset')) {
            $this->indexManager->reset($index);
        }

        if (!$this->providers->has($index->getName())) {
            $output->writeln('<error>No provider configured for this index.</error>');
            return Command::FAILURE;
        }

        $batchSize = (int) $input->getOption('batch-size');
        $sleep = (int) $input->getOption('sleep');

        $this->dispatcher?->dispatch(new PrePopulateEvent($index->getName(), $batchSize));

        $provider = $this->providers->get($index->getName());
        $persister = $this->persisters->get($index->getName());
        $processed = 0;
        $buffer = [];

        foreach ($provider->provide($index, $batchSize) as $object) {
            $buffer[] = $object;
            if (count($buffer) >= $batchSize) {
                $persister->insertMany($index, $buffer);
                $processed += count($buffer);
                $buffer = [];
                $this->dispatcher?->dispatch(new BatchProcessedEvent($index->getName(), $processed, $batchSize));
                if ($sleep > 0) {
                    usleep($sleep * 1000);
                }
            }
        }

        if (!empty($buffer)) {
            $persister->insertMany($index, $buffer);
            $processed += count($buffer);
            $this->dispatcher?->dispatch(new BatchProcessedEvent($index->getName(), $processed, $batchSize));
        }

        $this->dispatcher?->dispatch(new PostPopulateEvent($index->getName(), $processed));

        $output->writeln(sprintf('Index "%s" populated with %d objects.', $index->getIndexName(), $processed));

        return Command::SUCCESS;
    }
}
