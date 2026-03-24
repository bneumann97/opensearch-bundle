<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Provider;

use Bneumann\OpensearchBundle\Index\IndexDefinition;

interface ProviderInterface
{
    /** @return iterable<object> */
    public function provide(IndexDefinition $index, int $batchSize): iterable;
}
