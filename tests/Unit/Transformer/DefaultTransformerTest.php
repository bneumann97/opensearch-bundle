<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Tests\Unit\Transformer;

use Bneumann\OpensearchBundle\Exception\TransformerException;
use Bneumann\OpensearchBundle\Index\IndexDefinition;
use Bneumann\OpensearchBundle\Transformer\DefaultTransformer;
use PHPUnit\Framework\TestCase;

final class DefaultTransformerTest extends TestCase
{
    private DefaultTransformer $transformer;
    private IndexDefinition $index;

    protected function setUp(): void
    {
        $this->transformer = new DefaultTransformer();
        $this->index = new IndexDefinition('test', 'test', 'default', [], [], [], [], [], [], null);
    }

    public function testTransformJsonSerializable(): void
    {
        $object = new class implements \JsonSerializable {
            public function jsonSerialize(): array
            {
                return ['name' => 'Product', 'price' => 9.99];
            }
        };

        $result = $this->transformer->transform($object, $this->index);

        self::assertSame(['name' => 'Product', 'price' => 9.99], $result);
    }

    public function testTransformJsonSerializableNonArrayThrows(): void
    {
        $object = new class implements \JsonSerializable {
            public function jsonSerialize(): string
            {
                return 'not an array';
            }
        };

        $this->expectException(TransformerException::class);
        $this->expectExceptionMessage('jsonSerialize() must return an array.');

        $this->transformer->transform($object, $this->index);
    }

    public function testTransformToArray(): void
    {
        $object = new class {
            public function toArray(): array
            {
                return ['title' => 'Widget'];
            }
        };

        $result = $this->transformer->transform($object, $this->index);

        self::assertSame(['title' => 'Widget'], $result);
    }

    public function testTransformToArrayNonArrayThrows(): void
    {
        $object = new class {
            public function toArray(): string
            {
                return 'not array';
            }
        };

        $this->expectException(TransformerException::class);
        $this->expectExceptionMessage('toArray() must return an array.');

        $this->transformer->transform($object, $this->index);
    }

    public function testTransformPublicProperties(): void
    {
        $object = new class {
            public string $name = 'Gadget';
            public int $price = 42;
        };

        $result = $this->transformer->transform($object, $this->index);

        self::assertSame(['name' => 'Gadget', 'price' => 42], $result);
    }

    public function testTransformAccessors(): void
    {
        $object = new class {
            private string $name = 'Item';
            private bool $active = true;
            private bool $featured = false;

            public function getName(): string
            {
                return $this->name;
            }

            public function isActive(): bool
            {
                return $this->active;
            }

            public function hasFeatured(): bool
            {
                return $this->featured;
            }
        };

        $result = $this->transformer->transform($object, $this->index);

        self::assertSame('Item', $result['name']);
        self::assertTrue($result['active']);
        self::assertFalse($result['featured']);
    }

    public function testTransformSkipsNonScalarValues(): void
    {
        $object = new class {
            private string $name = 'Test';

            public function getName(): string
            {
                return $this->name;
            }

            public function getRelated(): object
            {
                return new \stdClass();
            }
        };

        $result = $this->transformer->transform($object, $this->index);

        self::assertSame(['name' => 'Test'], $result);
    }

    public function testTransformSkipsStaticMethods(): void
    {
        $object = new class {
            private string $name = 'Test';

            public function getName(): string
            {
                return $this->name;
            }

            public static function getStatic(): string
            {
                return 'static';
            }
        };

        $result = $this->transformer->transform($object, $this->index);

        self::assertSame(['name' => 'Test'], $result);
    }

    public function testTransformSkipsMethodsWithRequiredParams(): void
    {
        $object = new class {
            private string $name = 'Test';

            public function getName(): string
            {
                return $this->name;
            }

            public function getFormatted(string $format): string
            {
                return $format . $this->name;
            }
        };

        $result = $this->transformer->transform($object, $this->index);

        self::assertSame(['name' => 'Test'], $result);
    }

    public function testTransformAllowsArrayValues(): void
    {
        $object = new class {
            private array $tags = ['php', 'opensearch'];

            public function getTags(): array
            {
                return $this->tags;
            }
        };

        $result = $this->transformer->transform($object, $this->index);

        self::assertSame(['tags' => ['php', 'opensearch']], $result);
    }

    public function testTransformAllowsNullValues(): void
    {
        $object = new class {
            public function getDescription(): ?string
            {
                return null;
            }
        };

        $result = $this->transformer->transform($object, $this->index);

        self::assertArrayHasKey('description', $result);
        self::assertNull($result['description']);
    }
}
