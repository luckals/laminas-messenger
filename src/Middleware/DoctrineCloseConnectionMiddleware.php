<?php

declare(strict_types=1);

namespace TMV\Laminas\Messenger\Middleware;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ConsumedByWorkerStamp;

class DoctrineCloseConnectionMiddleware extends AbstractDoctrineMiddleware
{
    protected function handleForManager(
        EntityManagerInterface $entityManager,
        Envelope $envelope,
        StackInterface $stack
    ): Envelope {
        try {
            $connection = $entityManager->getConnection();

            return $stack->next()->handle($envelope, $stack);
        } finally {
            if (null !== $envelope->last(ConsumedByWorkerStamp::class)) {
                $connection->close();
            }
        }
    }
}
