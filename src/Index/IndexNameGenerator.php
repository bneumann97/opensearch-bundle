<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Index;

final class IndexNameGenerator
{
    public function generatePhysicalName(string $baseName): string
    {
        return sprintf('%s_%s', $baseName, (new \DateTimeImmutable('now'))->format('YmdHis'));
    }
}
