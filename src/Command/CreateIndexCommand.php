<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Command;

use Bneumann\OpensearchBundle\Index\IndexManagerInterface;
use Bneumann\OpensearchBundle\Index\IndexRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'opensearch:index:create', description: 'Create an OpenSearch index.')]
final class CreateIndexCommand extends Command
{
    public function __construct(
        private readonly IndexRegistry $indexRegistry,
        private readonly IndexManagerInterface $indexManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('index', InputArgument::REQUIRED, 'Index name as configured')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Delete existing index first');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $index = $this->indexRegistry->get((string) $input->getArgument('index'));
        $this->indexManager->create($index, (bool) $input->getOption('force'));

        $output->writeln(sprintf('Index "%s" created.', $index->getIndexName()));

        return Command::SUCCESS;
    }
}
