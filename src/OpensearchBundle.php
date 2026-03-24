<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle;

use Bneumann\OpensearchBundle\DependencyInjection\OpensearchExtension;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

final class OpensearchBundle extends AbstractBundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new OpensearchExtension();
    }
}
