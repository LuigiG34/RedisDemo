<?php

namespace App\MessageHandler;

use App\Entity\Task;
use App\Message\TaskProcessMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class TaskProcessMessageHandler
{
    public function __construct(private EntityManagerInterface $em) {}

    public function __invoke(TaskProcessMessage $message): void
    {
        $task = $this->em->getRepository(Task::class)->find($message->getTaskId());
        if (!$task) {
            return; // idempotent
        }

        $task->setStatus('processed');
        $task->setProcessedAt(new \DateTimeImmutable());

        $this->em->flush();
        // Notre Subscriber Doctrine invalide le cache automatiquement
    }
}
