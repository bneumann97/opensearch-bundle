<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Template;

use Bneumann\OpensearchBundle\Client\ClientCallerTrait;
use Bneumann\OpensearchBundle\Client\ClientRegistryInterface;
use Bneumann\OpensearchBundle\Exception\TemplateException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Throwable;

final class TemplateManager implements TemplateManagerInterface
{
    use ClientCallerTrait;

    public function __construct(private readonly ClientRegistryInterface $clients, ?EventDispatcherInterface $dispatcher = null)
    {
        $this->setEventDispatcher($dispatcher);
    }

    public function put(array $template): void
    {
        $client = $this->clients->get($template['client']);

        $params = [
            'name' => $template['template_name'],
            'body' => [
                'index_patterns' => $template['index_patterns'],
                'settings' => $template['settings'],
                'mappings' => $template['mappings'],
                'aliases' => $template['aliases'],
            ],
        ];

        try {
            $this->callClient('indices.put_template', $params,
                fn (array $params) => $client->indices()->putTemplate($params)
            );
        } catch (Throwable $e) {
            throw new TemplateException(sprintf('Failed to put index template "%s".', $template['template_name']), 0, $e);
        }
    }

    public function delete(string $name, string $client): void
    {
        $clientInstance = $this->clients->get($client);

        try {
            $this->callClient('indices.delete_template', ['name' => $name],
                fn (array $params) => $clientInstance->indices()->deleteTemplate($params)
            );
        } catch (Throwable $e) {
            throw new TemplateException(sprintf('Failed to delete index template "%s".', $name), 0, $e);
        }
    }
}
