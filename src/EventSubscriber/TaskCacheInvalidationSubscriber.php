<?php

namespace App\EventSubscriber;

use App\Entity\Task;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class TaskCacheInvalidationSubscriber implements EventSubscriber
{
    public function __construct(private TagAwareCacheInterface $taskCache) {}

    public function getSubscribedEvents(): array
    {
        return [Events::postPersist, Events::postUpdate, Events::postRemove];
    }

    public function postPersist(PostPersistEventArgs $args): void   { $this->invalidateIfTask($args->getObject()); }
    public function postUpdate(PostUpdateEventArgs $args): void     { $this->invalidateIfTask($args->getObject()); }
    public function postRemove(PostRemoveEventArgs $args): void     { $this->invalidateIfTask($args->getObject()); }

    private function invalidateIfTask(object $entity): void
    {
        if ($entity instanceof Task) {
            $this->taskCache->invalidateTags(['task.all']);
        }
    }
}
