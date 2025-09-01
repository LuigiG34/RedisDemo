<?php

namespace App\Tests\Functional;

use App\Entity\Task;
use App\Message\TaskProcessMessage;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessageProcessingTest extends WebTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        // Reset any previous Kernel state between tests
        self::ensureKernelShutdown(); 

        // Fetch entity manager so we can create tables and insert data
        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        // Wipe the database clean and create the tables
        // Garantees each test runs on a fresh DB
        $tool = new SchemaTool($this->em);
        $meta = $this->em->getMetadataFactory()->getAllMetadata();
        $tool->dropDatabase();
        if ($meta) {
            $tool->createSchema($meta);
        }
    }

    /**
     * Test that when we dispatch, the handler runs and updates the DB
     * @return void
     */
    public function testDispatchingMessageProcessesTaskImmediatelyInTestEnv(): void
    {
        // create a pending task
        $task = (new Task())
            ->setName('To process')
            ->setStatus('pending')
            ->setPriority(2)
            ->setRetryCount(0)
            ->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($task);
        $this->em->flush();

        // Sanity check : make sure INSERT worked
        $id = $task->getId();
        $this->assertNotNull($id);

        // Get the messenger bus and dispatch a message with the Task ID
        $bus = self::getContainer()->get(MessageBusInterface::class);
        $bus->dispatch(new TaskProcessMessage($id));

        // Detach all managed entities so next find hits the DB
        $this->em->clear();
        // Reload the task by ID
        $reloaded = $this->em->getRepository(Task::class)->find($id);

        // Check status is indeed "processed" and processedAt is not null
        $this->assertSame('processed', $reloaded->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $reloaded->getProcessedAt());
    }
}
