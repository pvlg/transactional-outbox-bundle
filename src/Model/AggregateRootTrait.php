<?php

declare(strict_types=1);

namespace Pvlg\Bundle\TransactionalOutboxBundle\Model;

use Pvlg\Bundle\TransactionalOutboxBundle\Event\DomainEventInterface;

trait AggregateRootTrait
{
    private array $events = [];

    public function popDomainEvents(): array
    {
        $events = $this->events;
        $this->events = [];

        return $events;
    }

    public function raiseDomainEvent(DomainEventInterface $event): void
    {
        $this->events[] = $event;
    }
}
