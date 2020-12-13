<?php

declare(strict_types=1);

namespace TMV\Laminas\Messenger\Handler;

use Symfony\Component\Messenger\Stamp\BusNameStamp;
use function class_implements;
use function class_parents;
use function get_class;
use function in_array;
use function is_callable;
use function is_string;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Handler\HandlerDescriptor;
use Symfony\Component\Messenger\Handler\HandlersLocatorInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use TMV\Laminas\Messenger\Exception\InvalidArgumentException;

final class ContainerHandlersLocator implements HandlersLocatorInterface
{
    /** @var ContainerInterface */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getHandlers(Envelope $envelope): iterable
    {
        $handlers = $this->getHandlersFromConfig($envelope);
        $seen = [];

        foreach (self::listTypes($envelope) as $type) {
            foreach ($handlers[$type] ?? [] as $handlerDescriptor) {
                if (is_callable($handlerDescriptor)) {
                    /** @psalm-suppress PossiblyInvalidArgument */
                    $handlerDescriptor = new HandlerDescriptor($handlerDescriptor);
                } elseif (is_string($handlerDescriptor)) {
                    $handlerDescriptor = new HandlerDescriptor($this->container->get($handlerDescriptor));
                }

                if (! $handlerDescriptor instanceof HandlerDescriptor) {
                    throw new InvalidArgumentException('Invalid handler descriptor provided');
                }

                if (! $this->shouldHandle($envelope, $handlerDescriptor)) {
                    continue;
                }
                $name = $handlerDescriptor->getName();
                if (in_array($name, $seen, true)) {
                    continue;
                }
                $seen[] = $name;
                yield $handlerDescriptor;
            }
        }
    }

    /**
     * @param Envelope $envelope
     * @return string[][]|array<string, array<string|callable>>
     */
    private function getHandlersFromConfig(Envelope $envelope): array
    {
        /** @var BusNameStamp $busNameStamp */
        $busNameStamp = $envelope->last(BusNameStamp::class);
        $busName = $busNameStamp ? $busNameStamp->getBusName() : 'messenger.bus.default';

        /** @var array<string, mixed> $config */
        $config = $this->container->has('config') ? $this->container->get('config') : [];
        /** @var string[][]|array<string, array<string|callable>> $handlerDescriptors */
        return $config['messenger']['buses'][$busName]['handlers'] ?? [];
    }

    /**
     * @param Envelope $envelope
     *
     * @return array<string, string>
     */
    private static function listTypes(Envelope $envelope): array
    {
        /** @var string $class */
        $class = get_class($envelope->getMessage());

        return [$class => $class]
            + class_parents($class)
            + class_implements($class)
            + ['*' => '*'];
    }

    private function shouldHandle(Envelope $envelope, HandlerDescriptor $handlerDescriptor): bool
    {
        /** @var ReceivedStamp|null $received */
        $received = $envelope->last(ReceivedStamp::class);
        if ($received === null) {
            return true;
        }
        $expectedTransport = $handlerDescriptor->getOption('from_transport');
        if ($expectedTransport === null) {
            return true;
        }

        return $received->getTransportName() === $expectedTransport;
    }
}
