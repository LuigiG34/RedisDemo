<?php

namespace App\Tests\Unit\Service;

use App\Dto\TaskListItem;
use App\Entity\Task;
use App\Repository\TaskRepository;
use App\Service\TaskReadModel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class TaskReadModelTest extends TestCase
{
    /**
     * Helper : behaves like real cache but fast and isolated
     * Tag-aware cache that lives in memory for test duration
     * @return TagAwareAdapter
     */
    private function makeCache(): TagAwareCacheInterface
    {
        return new TagAwareAdapter(new ArrayAdapter());
    }

    /**
     * Builds a real Task entity with fields for read model
     * @param int $id
     * @param string $name
     * @param string $status
     * @param int $priority
     * @return Task
     */
    private function makeTask(int $id, string $name = 'N', string $status = 'pending', int $priority = 1): Task
    {
        $t = new Task();
        $t->setName($name);
        $t->setStatus($status);
        $t->setPriority($priority);
        $t->setCreatedAt(new \DateTimeImmutable());
        // We use a reflection to set the id since we dont have a public setter
        $refId = new \ReflectionProperty(Task::class, 'id');
        $refId->setAccessible(true);
        $refId->setValue($t, $id);
        return $t;
    }

    /**
     * Load data from DB, cache the DTOs and then load from Cache
     * @return void
     */
    public function testListWithSourceCachesDtosAndReturnsSourceFlags(): void
    {
        // Create mock of repo to test : call findBy only once and return 2 Task entities
        $repo = $this->createMock(TaskRepository::class);
        $repo->expects($this->once())->method('findBy')->willReturn([
            $this->makeTask(1, 'A', 'pending', 1),
            $this->makeTask(2, 'B', 'done', 2),
        ]);

        // Build the real TaskReadModel
        $cache = $this->makeCache();
        $read  = new TaskReadModel($cache, $repo);

        // First call => Miss the cache, Load from repo, map to DTOs and return source 'db'
        [$items1, $src1] = $read->listWithSource();
        $this->assertSame('db', $src1);
        $this->assertContainsOnlyInstancesOf(TaskListItem::class, $items1); // Ensure mapping happened
        $this->assertCount(2, $items1);

        // Second call => Load from cache not the repo
        [$items2, $src2] = $read->listWithSource();
        $this->assertSame('cache', $src2);
        $this->assertCount(2, $items2);
    }

    /**
     * Invalidate the cache
     * @return void
     */
    public function testInvalidateAllCallsCacheInvalidateTags(): void
    {
        // Mock the cache and expect it to be invalidated
        $cache = $this->createMock(TagAwareCacheInterface::class);
        $cache->expects($this->once())->method('invalidateTags')->with(['task.all']);

        // Create mock repo for TaskReadModel
        $repo = $this->createMock(TaskRepository::class);

        // invalidate the cache
        $read = new TaskReadModel($cache, $repo);
        $read->invalidateAll();
    }
}
