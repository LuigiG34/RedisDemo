<?php

namespace App\Service;

use App\Enum\QueuePriority;
use App\Message\TaskProcessMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;

final class TaskQueue
{
    public function __construct(private MessageBusInterface $bus) {}

    public function enqueueProcess(int $taskId, QueuePriority $priority = QueuePriority::NORMAL): void
    {
        $transport = match ($priority) {
            QueuePriority::HIGH   => 'async_high',
            QueuePriority::LOW    => 'async_low',
            default               => 'async',
        };

        $this->bus->dispatch(
            new TaskProcessMessage($taskId),
            [new TransportNamesStamp([$transport])]
        );
    }
}
