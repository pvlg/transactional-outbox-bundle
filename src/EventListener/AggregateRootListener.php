<?php

declare(strict_types=1);

namespace Pvlg\Bundle\TransactionalOutboxBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Pvlg\Bundle\TransactionalOutboxBundle\Event\DomainEventInterface;
use Pvlg\Bundle\TransactionalOutboxBundle\Model\AggregateRootInterface;
use Pvlg\Bundle\TransactionalOutboxBundle\Model\CreatableInterface;
use Pvlg\Bundle\TransactionalOutboxBundle\Model\DeletableInterface;
use Pvlg\Bundle\TransactionalOutboxBundle\Model\UpdatableInterface;
use Pvlg\Bundle\TransactionalOutboxBundle\Service\OutboxQueue;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::postRemove)]
final class AggregateRootListener
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
        private OutboxQueue $outboxQueue,
    ) {}

    public function postPersist(PostPersistEventArgs $event): void
    {
        $object = $event->getObject();
        $connection = $event->getObjectManager()->getConnection();

        if ($object instanceof CreatableInterface) {
            $object->raiseCreatedDomainEvent();
        }

        $this->dispatchEvents($event, $connection);
    }

    public function postUpdate(PostUpdateEventArgs $event): void
    {
        $object = $event->getObject();
        $connection = $event->getObjectManager()->getConnection();

        if ($object instanceof UpdatableInterface) {
            $object->raiseUpdatedDomainEvent();
        }

        $this->dispatchEvents($event, $connection);
    }

    public function postRemove(PostRemoveEventArgs $event): void
    {
        $object = $event->getObject();
        $connection = $event->getObjectManager()->getConnection();

        if ($object instanceof DeletableInterface) {
            $object->raiseDeletedDomainEvent();
        }

        $this->dispatchEvents($event, $connection);
    }

    private function dispatchEvents(PostPersistEventArgs|PostRemoveEventArgs|PostUpdateEventArgs $event, Connection $connection): void
    {
        $object = $event->getObject();

        if (!$object instanceof AggregateRootInterface) {
            return;
        }

        foreach ($object->popDomainEvents() as $event) {
            $this->dispatch($event, $connection);
        }
    }

    private function dispatch(DomainEventInterface $event, Connection $connection): void
    {
        $this->outboxQueue->send($event, $connection);
        $this->dispatcher->dispatch($event);
    }
}
