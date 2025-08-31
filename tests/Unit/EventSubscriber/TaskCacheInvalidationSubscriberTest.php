<?php

namespace App\Tests\Unit\EventSubscriber;

use App\Entity\Task;
use App\EventSubscriber\TaskCacheInvalidationSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class TaskCacheInvalidationSubscriberTest extends TestCase
{
    /**
     * Ensure 3 invalidations for Task on persist/update/remove.
     * @return void
     */
    public function testInvalidatesOnPersistUpdateRemoveForTask(): void
    {
        // We expect invalidateTags to be called 3 times on this mock with the arg 'task.all'
        $cache = $this->createMock(TagAwareCacheInterface::class);
        $cache->expects($this->exactly(3))
              ->method('invalidateTags')
              ->with(['task.all']);

        // We create the subscriber and inject ou mock cache inside
        // It will call invalidateTags each time we change a Task
        $subscriber = new TaskCacheInvalidationSubscriber($cache);

        // We make a real Task entity because the subscriber check the instanceof
        $task = new Task();

        // Mock the EntityManager to satisfy the Doctrine event args constructors
        $em = $this->createMock(EntityManagerInterface::class);

        // We simulate Doctrine firing postPersist, postUpdate and postRemove which should trigger invalidateTags 3 times
        $subscriber->postPersist(new PostPersistEventArgs($task, $em));
        $subscriber->postUpdate(new PostUpdateEventArgs($task, $em));
        $subscriber->postRemove(new PostRemoveEventArgs($task, $em));
    }

    /**
     * Ensure 0 invalidations for non-Task entities
     * @return void
     */
    public function testIgnoresOtherEntities(): void
    {
        // Mock cache, we expect 0 calls to invalidateTags
        $cache = $this->createMock(TagAwareCacheInterface::class);
        $cache->expects($this->never())->method('invalidateTags');

        // Create subscriber with our Mock
        $subscriber = new TaskCacheInvalidationSubscriber($cache);

        // A non-Task object is created to simulate another type of Entity
        $other = new \stdClass();

        // Mock the EntityManager to satisfy the Doctrine event args constructors
        $em = $this->createMock(EntityManagerInterface::class);

        // We simulate Doctrine firing postPersist, postUpdate and postRemove which should trigger invalidateTags 3 times
        $subscriber->postPersist(new PostPersistEventArgs($other, $em));
        $subscriber->postUpdate(new PostUpdateEventArgs($other, $em));
        $subscriber->postRemove(new PostRemoveEventArgs($other, $em));

        // Simply to avoid PHPUnit classifying this as a risky test
        $this->assertTrue(true);
    }
}
