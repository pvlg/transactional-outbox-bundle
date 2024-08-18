<?php

declare(strict_types=1);

namespace Pvlg\Bundle\TransactionalOutboxBundle\Model;

interface CreatableInterface
{
    public function raiseCreatedDomainEvent(): void;
}
