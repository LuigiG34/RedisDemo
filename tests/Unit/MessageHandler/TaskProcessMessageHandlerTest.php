<?php

namespace App\Tests\Unit\MessageHandler;

use App\Entity\Task;
use App\Message\TaskProcessMessage;
use App\MessageHandler\TaskProcessMessageHandler;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;         // <-- use the concrete class
use PHPUnit\Framework\TestCase;

final class TaskProcessMessageHandlerTest extends TestCase
{
    /**
     * Handler loads, mutates and flushes
     * @return void
     */
    public function testHandlerProcessesExistingTask(): void
    {
        // Create a real Task entity with an initial pending status
        $task = new Task();
        $task->setStatus('pending');

        // Mock a Doctrine\ORM\EntityRepository (constructor disabled)
        $repo = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find'])
            ->getMock();

        // Stub the repo when find(123) is called and return our Task
        $repo->method('find')->with(123)->willReturn($task);

        // Mock EntityManager
        $em = $this->createMock(EntityManagerInterface::class);

        // getRepository return our mocked EntityRepository
        $em->method('getRepository')->with(Task::class)->willReturn($repo);
        // Expect flush to be called 1 (handler will update entity and flush)
        $em->expects($this->once())->method('flush');

        // Instantiate the handle with our mocked $em
        $handler = new TaskProcessMessageHandler($em);

        // Invoke the handler like Messenger would carying the task id 123
        $handler(new TaskProcessMessage(123));

        // Status became processed and processedAt is set (we check the type)
        $this->assertSame('processed', $task->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $task->getProcessedAt());
    }

    /**
     * Handler does nothing so flush never happened
     * @return void
     */
    public function testHandlerDoesNothingIfTaskMissing(): void
    {
        // We mock EntityRepository again
        $repo = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find'])
            ->getMock();
        
        // Stub find to return null
        $repo->method('find')->with(999)->willReturn(null);

        // Mock $em and return our repo
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->with(Task::class)->willReturn($repo);
        // Assert flush never happens
        $em->expects($this->never())->method('flush');

        // Run the handler with a non existing ID
        $handler = new TaskProcessMessageHandler($em);
        $handler(new TaskProcessMessage(999));

        // avoid risky test
        $this->addToAssertionCount(1);
    }
}
