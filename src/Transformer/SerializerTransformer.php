<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Transformer;

use Bneumann\OpensearchBundle\Index\IndexDefinition;
use Symfony\Component\Serializer\SerializerInterface;

final class SerializerTransformer implements TransformerInterface
{
    public function __construct(private readonly SerializerInterface $serializer)
    {
    }

    public function transform(object $object, IndexDefinition $index): array
    {
        $config = $index->getSerializerConfig();
        $context = $config['context'] ?? [];

        if (!empty($config['groups'])) {
            $context['groups'] = $config['groups'];
        }

        $data = $this->serializer->normalize($object, 'json', $context);

        return is_array($data) ? $data : ['value' => $data];
    }
}
