<?php

declare(strict_types=1);

use Pvlg\Bundle\TransactionalOutboxBundle\Command\ConsumeCommand;
use Pvlg\Bundle\TransactionalOutboxBundle\EventListener\AggregateRootListener;
use Pvlg\Bundle\TransactionalOutboxBundle\EventListener\GenerateDoctrineSchemaListener;
use Pvlg\Bundle\TransactionalOutboxBundle\Service\OutboxQueue;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services
        ->defaults()
        ->autowire()
        ->autoconfigure()
    ;

    $services->set(ConsumeCommand::class);
    $services->set(OutboxQueue::class)->arg('$connectionRegistry', service('doctrine'));
    $services->set(AggregateRootListener::class);
    $services->set(GenerateDoctrineSchemaListener::class);
};
