<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'opensearch:debug:config', description: 'Print resolved OpenSearch bundle configuration.')]
final class DebugConfigCommand extends Command
{
    public function __construct(private readonly array $config)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(json_encode($this->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return Command::SUCCESS;
    }
}
