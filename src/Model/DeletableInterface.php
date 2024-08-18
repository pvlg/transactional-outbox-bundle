<?php

declare(strict_types=1);

namespace Pvlg\Bundle\TransactionalOutboxBundle\Model;

interface DeletableInterface
{
    public function raiseDeletedDomainEvent(): void;
}
