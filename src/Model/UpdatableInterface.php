<?php

declare(strict_types=1);

namespace Pvlg\Bundle\TransactionalOutboxBundle\Model;

interface UpdatableInterface
{
    public function raiseUpdatedDomainEvent(): void;
}
