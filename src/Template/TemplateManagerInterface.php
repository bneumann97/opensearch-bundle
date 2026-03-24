<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Template;

interface TemplateManagerInterface
{
    public function put(array $template): void;

    public function delete(string $name, string $client): void;
}
