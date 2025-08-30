<?php

namespace App\Service;

use App\Enum\QueuePriority;
use App\Message\TaskProcessMessage;
use App\Repository\TaskRepository;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;

final class TaskQueue
{
    public function __construct(
        private MessageBusInterface $bus,
        private TaskRepository $repo
    ) {}

    /** Dispatch a task by reading its priority from DB and mapping to the right transport. */
    public function enqueueFromDb(int $taskId): void
    {
        $task = $this->repo->find($taskId);
        if (!$task) {
            return;
        }

        $enum = QueuePriority::fromInt((int) ($task->getPriority() ?? 2));
        $this->bus->dispatch(
            new TaskProcessMessage($taskId),
            [new TransportNamesStamp([$enum->transport()])]
        );
    }

    /** Bulk version if you already have rows like [['id'=>1,'priority'=>2], ...] */
    public function enqueueManyByRows(array $rows): void
    {
        foreach ($rows as $row) {
            $id   = (int) $row['id'];
            $prio = (int) ($row['priority'] ?? 2);
            $enum = QueuePriority::fromInt($prio);

            $this->bus->dispatch(
                new TaskProcessMessage($id),
                [new TransportNamesStamp([$enum->transport()])]
            );
        }
    }
}
