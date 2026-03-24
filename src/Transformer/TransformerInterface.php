<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Transformer;

use Bneumann\OpensearchBundle\Index\IndexDefinition;

interface TransformerInterface
{
    /** @return array<string, mixed> */
    public function transform(object $object, IndexDefinition $index): array;
}
