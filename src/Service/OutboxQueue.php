<?php

declare(strict_types=1);

namespace Pvlg\Bundle\TransactionalOutboxBundle\Service;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ConnectionRegistry;
use Pvlg\Bundle\TransactionalOutboxBundle\Event\DomainEventInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\SerializerInterface;

final class OutboxQueue
{
    /**
     * @param array<mixed> $queuesConfig
     */
    public function __construct(
        private ConnectionRegistry $connectionRegistry,
        private SerializerInterface $serializer,
        #[Autowire(param: 'transactional_outbox.queues')]
        private array $queuesConfig,
    ) {}

    /**
     * @return null|array<int, mixed>
     */
    public function get(string $queueName): ?array
    {
        $connection = $this->getConnection($queueName);

        $qb = $connection->createQueryBuilder();
        $qb->select('*');
        $qb->from($this->getTableName($queueName));
        $qb->orderBy('id', 'ASC');
        $qb->setMaxResults(1);
        $row = $qb->fetchAssociative();

        return $row === false ? null : $row;
    }

    public function remove(int $id, string $queueName): void
    {
        $connection = $this->getConnection($queueName);

        $connection->createQueryBuilder()
            ->delete($this->getTableName($queueName))
            ->where('id = ?')
            ->setParameter(0, $id)
            ->executeStatement()
        ;
    }

    public function send(DomainEventInterface $event, Connection $connection): void
    {
        $currentConnectionName = null;
        foreach (array_keys($this->connectionRegistry->getConnectionNames()) as $connectionName) {
            if ($this->connectionRegistry->getConnection($connectionName) === $connection) {
                $currentConnectionName = $connectionName;

                break;
            }
        }

        foreach ($this->queuesConfig as $queueName => $queueConfig) {
            if ($queueConfig['connection_name'] !== $currentConnectionName) {
                continue;
            }

            $now = new DateTimeImmutable('UTC');
            $queryBuilder = $connection->createQueryBuilder()
                ->insert($queueConfig['table_name'])
                ->values([
                    'body' => '?',
                    'type' => '?',
                    'created_at' => '?',
                ])
                ->setParameters([
                    $this->serializer->serialize($event, 'json'),
                    $event::class,
                ])
                ->setParameter('created_at', $now, Types::DATETIME_IMMUTABLE)
            ;

            $queryBuilder->executeStatement();
        }
    }

    private function getTableName(string $queueName): string
    {
        return $this->queuesConfig[$queueName]['table_name'];
    }

    private function getConnection(string $queueName): Connection
    {
        $queueConfig = $this->queuesConfig[$queueName];

        return $this->connectionRegistry->getConnection($queueConfig['connection_name']);
    }
}
