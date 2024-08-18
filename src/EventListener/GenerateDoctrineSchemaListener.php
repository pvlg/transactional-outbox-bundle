<?php

declare(strict_types=1);

namespace Pvlg\Bundle\TransactionalOutboxBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\ToolEvents;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsDoctrineListener(event: ToolEvents::postGenerateSchema)]
final class GenerateDoctrineSchemaListener
{
    /**
     * @param array<mixed> $queuesConfig
     */
    public function __construct(
        #[Autowire(param: 'transactional_outbox.queues')]
        private array $queuesConfig,
    ) {}

    public function postGenerateSchema(GenerateSchemaEventArgs $event): void
    {
        foreach ($this->queuesConfig as $queueConfig) {
            $this->generateTable($event->getSchema(), $queueConfig['table_name']);
        }
    }

    private function generateTable(Schema $schema, string $tableName): void
    {
        $table = $schema->createTable($tableName);

        $table
            ->addColumn('id', Types::BIGINT)
            ->setAutoincrement(true)
        ;
        $table
            ->addColumn('body', Types::TEXT)
            ->setNotnull(true)
        ;
        $table
            ->addColumn('type', Types::TEXT)
            ->setNotnull(true)
        ;
        $table
            ->addColumn('created_at', Types::DATETIMETZ_IMMUTABLE)
            ->setNotnull(true)
        ;
        $table->setPrimaryKey(['id']);
    }
}
