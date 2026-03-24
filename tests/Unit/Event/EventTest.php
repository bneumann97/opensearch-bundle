<?php

declare(strict_types=1);

namespace Bneumann\OpensearchBundle\Tests\Unit\Event;

use Bneumann\OpensearchBundle\Event\BatchProcessedEvent;
use Bneumann\OpensearchBundle\Event\PostPopulateEvent;
use Bneumann\OpensearchBundle\Event\PostRequestEvent;
use Bneumann\OpensearchBundle\Event\PostTransformEvent;
use Bneumann\OpensearchBundle\Event\PrePopulateEvent;
use Bneumann\OpensearchBundle\Event\PreRequestEvent;
use Bneumann\OpensearchBundle\Event\PreTransformEvent;
use PHPUnit\Framework\TestCase;

final class EventTest extends TestCase
{
    public function testPreRequestEvent(): void
    {
        $event = new PreRequestEvent('search', ['index' => 'products']);

        self::assertSame('search', $event->getOperation());
        self::assertSame(['index' => 'products'], $event->getParams());
    }

    public function testPostRequestEvent(): void
    {
        $exception = new \RuntimeException('fail');
        $event = new PostRequestEvent('search', ['index' => 'products'], ['hits' => []], $exception);

        self::assertSame('search', $event->getOperation());
        self::assertSame(['index' => 'products'], $event->getParams());
        self::assertSame(['hits' => []], $event->getResponse());
        self::assertSame($exception, $event->getException());
    }

    public function testPostRequestEventNullException(): void
    {
        $event = new PostRequestEvent('search', [], 'response', null);

        self::assertNull($event->getException());
        self::assertSame('response', $event->getResponse());
    }

    public function testPreTransformEvent(): void
    {
        $object = new \stdClass();
        $event = new PreTransformEvent($object, 'products');

        self::assertSame($object, $event->getObject());
        self::assertSame('products', $event->getIndexName());
        self::assertSame([], $event->getData());

        $event->setData(['modified' => true]);
        self::assertSame(['modified' => true], $event->getData());
    }

    public function testPostTransformEvent(): void
    {
        $object = new \stdClass();
        $event = new PostTransformEvent($object, 'products', ['name' => 'Test']);

        self::assertSame($object, $event->getObject());
        self::assertSame('products', $event->getIndexName());
        self::assertSame(['name' => 'Test'], $event->getData());

        $event->setData(['name' => 'Modified']);
        self::assertSame(['name' => 'Modified'], $event->getData());
    }

    public function testPrePopulateEvent(): void
    {
        $event = new PrePopulateEvent('products', 100);

        self::assertSame('products', $event->getIndexName());
        self::assertSame(100, $event->getBatchSize());
    }

    public function testPostPopulateEvent(): void
    {
        $event = new PostPopulateEvent('products', 500);

        self::assertSame('products', $event->getIndexName());
        self::assertSame(500, $event->getProcessed());
    }

    public function testBatchProcessedEvent(): void
    {
        $event = new BatchProcessedEvent('products', 200, 100);

        self::assertSame('products', $event->getIndexName());
        self::assertSame(200, $event->getProcessed());
        self::assertSame(100, $event->getBatchSize());
    }
}
