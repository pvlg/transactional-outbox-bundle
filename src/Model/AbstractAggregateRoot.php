<?php

declare(strict_types=1);

namespace Pvlg\Bundle\TransactionalOutboxBundle\Model;

abstract class AbstractAggregateRoot implements AggregateRootInterface
{
    use AggregateRootTrait;
}
