<?php

declare(strict_types=1);

namespace TMV\Laminas\Messenger\Factory\Handler;

use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Handler\HandlersLocatorInterface;
use TMV\Laminas\Messenger\Handler\ContainerHandlersLocator;

final class HandlersLocatorFactory
{
    public function __invoke(ContainerInterface $container): HandlersLocatorInterface
    {
        return new ContainerHandlersLocator($container);
    }
}
