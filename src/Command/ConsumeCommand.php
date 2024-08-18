<?php

declare(strict_types=1);

namespace Pvlg\Bundle\TransactionalOutboxBundle\Command;

use Psr\Log\LoggerInterface;
use Pvlg\Bundle\TransactionalOutboxBundle\Service\OutboxQueue;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\SerializerInterface;

use function sprintf;

#[AsCommand(name: 'outbox:consume')]
final class ConsumeCommand extends Command implements SignalableCommandInterface
{
    private bool $shouldStop = false;

    public function __construct(
        private OutboxQueue $outboxQueue,
        private MessageBusInterface $bus,
        private SerializerInterface $serializer,
        private LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $queueName = $input->getOption('queue');

        $this->logger->notice('Started');

        while (!$this->shouldStop) {
            $row = $this->outboxQueue->get($queueName);
            if ($row === null) {
                sleep(1);

                continue;
            }

            $this->logger->info(sprintf('Received message %s', $row['id']));

            $event = $this->serializer->deserialize($row['body'], $row['type'], 'json');

            $this->bus->dispatch($event);

            $this->outboxQueue->remove($row['id'], $queueName);
        }

        return Command::SUCCESS;
    }

    /**
     * @return array<int>
     */
    public function getSubscribedSignals(): array
    {
        return [SIGINT, SIGTERM];
    }

    public function handleSignal(int $signal, false|int $previousExitCode = 0): false|int
    {
        $this->shouldStop = true;

        return false;
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                name: 'queue',
                mode: InputOption::VALUE_OPTIONAL,
                default: 'default',
            )
        ;
    }
}
