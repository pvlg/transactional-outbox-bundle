<?php

declare(strict_types=1);

namespace Pvlg\Bundle\TransactionalOutboxBundle\Model;

use Pvlg\Bundle\TransactionalOutboxBundle\Event\DomainEventInterface;

interface AggregateRootInterface
{
    /**
     * @return DomainEventInterface[]
     */
    public function popDomainEvents(): array;

    public function raiseDomainEvent(DomainEventInterface $event): void;
}
