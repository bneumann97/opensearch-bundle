<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Command;

use Bneumann\OpensearchBundle\Template\TemplateManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'opensearch:templates:reset', description: 'Reset all configured index templates.')]
final class ResetTemplatesCommand extends Command
{
    /** @param array<int, array<string, mixed>> $templates */
    public function __construct(
        private readonly TemplateManagerInterface $templateManager,
        private readonly array $templates,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->templates as $template) {
            $this->templateManager->put($template);
        }

        $output->writeln('Index templates reset.');

        return Command::SUCCESS;
    }
}
