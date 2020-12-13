<?php

declare(strict_types=1);

namespace TMV\Laminas\Messenger\Test\Factory\Handler;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Handler\HandlerDescriptor;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\Handler\HandlersLocatorInterface;
use TMV\Laminas\Messenger\Factory\Handler\HandlersLocatorFactory;

class HandlersLocatorFactoryTest extends TestCase
{
    public function testFactory(): void
    {
        $factory = new HandlersLocatorFactory('bus_name');

        $container = $this->createStub(ContainerInterface::class);
        $service = $factory($container);

        $this->assertInstanceOf(HandlersLocatorInterface::class, $service);
    }
}
