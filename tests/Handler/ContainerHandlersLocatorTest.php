<?php

declare(strict_types=1);

namespace TMV\Laminas\Messenger\Test\Handler;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Handler\HandlerDescriptor;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\Stamp\BusNameStamp;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use TMV\Laminas\Messenger\Handler\ContainerHandlersLocator;
use TMV\Laminas\Messenger\Test\Factory\MessageMock;

class HandlersLocatorTest extends TestCase
{
    public function testItYieldsHandlerDescriptors(): void
    {
        /** @var HandlersLocator $service */
        $handler = $this->createPartialMock(HandlersLocatorTestCallable::class, ['__invoke']);
        $skippedHandler = $this->createPartialMock(HandlersLocatorTestCallable::class, ['__invoke']);

        $container = $this->prophesize(ContainerInterface::class);
        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn([
            'messenger' => [
                'buses' => [
                    'bus_name' => [
                        'handlers' => [
                            MessageMock::class => [
                                $handler,
                                $skippedHandler
                            ],
                        ],
                    ],
                ],
            ],
        ]);


        $locator = new ContainerHandlersLocator($container->reveal());

        $this->assertEquals(
            [new HandlerDescriptor($handler)],
            iterator_to_array($locator->getHandlers(new Envelope(new MessageMock(), [new BusNameStamp('bus_name')])))
        );
    }

    public function testItYieldsHandlerDescriptorsFromContainer(): void
    {
        $container = $this->prophesize(ContainerInterface::class);
        $handler = $this->createPartialMock(HandlersLocatorTestCallable::class, ['__invoke']);

        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn([
            'messenger' => [
                'buses' => [
                    'bus_name' => [
                        'handlers' => [
                            MessageMock::class => [
                                'foo-handler'
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        $container->has('foo-handler')->willReturn(true);
        $container->get('foo-handler')->willReturn($handler);

        $locator = new ContainerHandlersLocator($container->reveal());

        $this->assertEquals(
            [new HandlerDescriptor($handler)],
            iterator_to_array($locator->getHandlers(new Envelope(new MessageMock(), [new BusNameStamp('bus_name')])))
        );
    }

    public function testItReturnsOnlyHandlersMatchingTransport(): void
    {
        $container = $this->prophesize(ContainerInterface::class);
        $firstHandler = $this->createPartialMock(HandlersLocatorTestCallable::class, ['__invoke']);
        $secondHandler = $this->createPartialMock(HandlersLocatorTestCallable::class, ['__invoke']);

        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn([
            'messenger' => [
                'buses' => [
                    'bus_name' => [
                        'handlers' => [
                            MessageMock::class => [
                                $first = new HandlerDescriptor($firstHandler, ['alias' => 'one']),
                                new HandlerDescriptor($this->createPartialMock(HandlersLocatorTestCallable::class, ['__invoke']), ['from_transport' => 'ignored', 'alias' => 'two']),
                                $second = new HandlerDescriptor($secondHandler, ['from_transport' => 'transportName', 'alias' => 'three']),
                            ]
                        ],
                    ],
                ],
            ],
        ]);

        $locator = new ContainerHandlersLocator($container->reveal());

        $this->assertEquals([
            $first,
            $second,
        ], iterator_to_array($locator->getHandlers(
            new Envelope(new MessageMock('Body'), [new ReceivedStamp('transportName'), new BusNameStamp('bus_name')])
        )));
    }
}

class HandlersLocatorTestCallable
{
    public function __invoke()
    {
    }
}
