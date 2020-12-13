<?php

declare(strict_types=1);

namespace TMV\Laminas\Messenger\Test\Factory\Middleware;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use TMV\Laminas\Messenger\Exception\InvalidArgumentException;
use TMV\Laminas\Messenger\Factory\Middleware\HandleMessageMiddlewareFactory;

class HandleMessageMiddlewareFactoryTest extends TestCase
{
    public function testFactory(): void
    {
        $factory = [HandleMessageMiddlewareFactory::class, 'bus_name'];

        $container = $this->prophesize(ContainerInterface::class);

        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn([
            'messenger' => [
                'logger' => 'messenger.logger',
            ],
        ]);

        $container->get(HandlersLocator::class)->willReturn($this->createStub(HandlersLocator::class));

        $logger = $this->prophesize(LoggerInterface::class);

        $container->get('messenger.logger')
            ->shouldBeCalled()
            ->willReturn($logger->reveal());

        $service = $factory($container->reveal());

        $this->assertInstanceOf(HandleMessageMiddleware::class, $service);
    }

    public function testFactoryWithoutOptionalDependencies(): void
    {
        $factory = [HandleMessageMiddlewareFactory::class, 'bus_name'];

        $container = $this->prophesize(ContainerInterface::class);
        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn([]);
        $container->get(HandlersLocator::class)->willReturn($this->createMock(HandlersLocator::class));

        $service = $factory($container->reveal());

        $this->assertInstanceOf(HandleMessageMiddleware::class, $service);
    }

    public function testInvalidCall(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $factory = [HandleMessageMiddlewareFactory::class, 'bus_name'];
        $factory('foo');
    }
}
