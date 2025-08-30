<?php

namespace App\Service;

use App\Dto\TaskListItem;
use App\Entity\Task;
use App\Repository\TaskRepository;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class TaskReadModel
{
    public function __construct(
        private TagAwareCacheInterface $taskCache, // bound to cache.task.tagaware in services.yaml
        private TaskRepository $repo
    ) {}

    /**
     * Returns [TaskListItem[], 'cache'|'db'] so controller can display a flash.
     */
    public function listWithSource(): array
    {
        $wasComputed = false;

        $items = $this->taskCache->get('task.list', function (ItemInterface $item) use (&$wasComputed) {
            $wasComputed = true;
            $item->expiresAfter(600); // 10 minutes
            $item->tag(['task.all']);

            $entities = $this->repo->findBy([], ['createdAt' => 'DESC']);

            return array_map(
                fn(Task $t) => new TaskListItem(
                    id:          $t->getId(),
                    name:        (string) $t->getName(),
                    status:      (string) $t->getStatus(),
                    priority:    (int) $t->getPriority(),
                    assignedTo:  $t->getAssignedTo(),
                    createdAt:   $t->getCreatedAt()?->format(DATE_ATOM) ?? '',
                    processedAt: $t->getProcessedAt()?->format(DATE_ATOM),
                    retryCount:  (int) $t->getRetryCount(),
                    done:        $t->getStatus() === 'done'
                ),
                $entities
            );
        });

        return [$items, $wasComputed ? 'db' : 'cache'];
    }

    public function invalidateAll(): void
    {
        $this->taskCache->invalidateTags(['task.all']);
    }
}
