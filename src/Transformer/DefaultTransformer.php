<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Transformer;

use Bneumann\OpensearchBundle\Exception\TransformerException;
use Bneumann\OpensearchBundle\Index\IndexDefinition;

final class DefaultTransformer implements TransformerInterface
{
    public function transform(object $object, IndexDefinition $index): array
    {
        if ($object instanceof \JsonSerializable) {
            $data = $object->jsonSerialize();
            if (!is_array($data)) {
                throw new TransformerException('jsonSerialize() must return an array.');
            }

            return $data;
        }

        if (method_exists($object, 'toArray')) {
            $data = $object->toArray();
            if (!is_array($data)) {
                throw new TransformerException('toArray() must return an array.');
            }

            return $data;
        }

        $data = get_object_vars($object);
        if (!empty($data)) {
            return $data;
        }

        return $this->extractFromAccessors($object);
    }

    private function extractFromAccessors(object $object): array
    {
        $data = [];
        $reflection = new \ReflectionObject($object);

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic() || $method->getNumberOfRequiredParameters() > 0) {
                continue;
            }

            $name = $method->getName();
            if ($name === '__toString') {
                continue;
            }

            $field = null;
            if (str_starts_with($name, 'get') && strlen($name) > 3) {
                $field = lcfirst(substr($name, 3));
            } elseif (str_starts_with($name, 'is') && strlen($name) > 2) {
                $field = lcfirst(substr($name, 2));
            } elseif (str_starts_with($name, 'has') && strlen($name) > 3) {
                $field = lcfirst(substr($name, 3));
            }

            if ($field === null || $field === '') {
                continue;
            }

            $value = $method->invoke($object);
            if (is_scalar($value) || $value === null || is_array($value)) {
                $data[$field] = $value;
            }
        }

        return $data;
    }
}
