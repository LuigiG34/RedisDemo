<?php

namespace App\Tests\Unit\Service;

use App\Entity\Task;
use App\Message\TaskProcessMessage;
use App\Repository\TaskRepository;
use App\Service\TaskQueue;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;

final class TaskQueueTest extends TestCase
{
    /**
     * Helper to create a Task
     *
     * @param integer $id
     * @param integer $priority
     * @return Task
     */
    private function makeTask(int $id, int $priority): Task
    {
        $t = new Task();
        $t->setPriority($priority);
        // We use a reflection to set the id since we dont have a public setter
        $refId = new \ReflectionProperty(Task::class, 'id');
        $refId->setAccessible(true);
        $refId->setValue($t, $id);
        return $t;
    }

    /**
     * Extract the TransportNamesStamp from a stamps array
     * @param array $stamps
     * @return \Symfony\Component\Messenger\Stamp\TransportNamesStamp
     */
    private function extractTransportStamp(array $stamps): TransportNamesStamp
    {
        $filtered = array_values(array_filter($stamps, fn($s) => $s instanceof TransportNamesStamp));
        $this->assertNotEmpty($filtered, 'TransportNamesStamp not found on envelope');
        return $filtered[0];
    }

    /**
     * Load the Task, read the DB priority, map it to right transport and dispatch it
     * @return void
     */
    public function testEnqueueFromDbDispatchesToCorrectTransportByPriority(): void
    {
        // Create a Mock for the Repo and the Bus
        $repo = $this->createMock(TaskRepository::class);
        $bus = $this->createMock(MessageBusInterface::class);

        // Program the repo to return on 3 different calls
        $repo->method('find')->willReturnOnConsecutiveCalls(
            $this->makeTask(1, 1),
            $this->makeTask(2, 2),
            $this->makeTask(3, 3),
        );

        // Replace dispatch with a callback that pushes the message + stamps it into $captures
        // Returns a real Envelope (what bus usualy returns)
        $captures = [];
        $bus->method('dispatch')->willReturnCallback(function ($message, array $stamps = []) use (&$captures) {
            $captures[] = [$message, $stamps];
            return new Envelope($message, $stamps);
        });

        // Create the service with our mocks
        // Call the method three times for those 3 different IDs above and dispatch
        $queue = new TaskQueue($bus, $repo);
        $queue->enqueueFromDb(1);
        $queue->enqueueFromDb(2);
        $queue->enqueueFromDb(3);

        // 3 dispatches in $capture
        $this->assertCount(3, $captures);

        // Extract the TransportNamesStamp from the stamps array
        $s0 = $this->extractTransportStamp($captures[0][1]);
        $s1 = $this->extractTransportStamp($captures[1][1]);
        $s2 = $this->extractTransportStamp($captures[2][1]);

        // Assert the chosen transport match
        $this->assertSame(['async_low'], $s0->getTransportNames());
        $this->assertSame(['async'], $s1->getTransportNames());
        $this->assertSame(['async_high'], $s2->getTransportNames());

        // Sanity check : ensure each dispatch message is the correct class
        $this->assertInstanceOf(TaskProcessMessage::class, $captures[0][0]);
        $this->assertInstanceOf(TaskProcessMessage::class, $captures[1][0]);
        $this->assertInstanceOf(TaskProcessMessage::class, $captures[2][0]);
    }

    /**
     * If task doesnt exist -> don't dispatch it
     * @return void
     */
    public function testEnqueueFromDbDoesNothingIfTaskMissing(): void
    {
        // Simulate no Task found in DB
        $repo = $this->createMock(TaskRepository::class);
        $repo->method('find')->willReturn(null);

        // Expect nothing is dispatched when task doesn't exist
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->never())->method('dispatch');

        // Call the method with a missing ID
        $queue = new TaskQueue($bus, $repo);
        $queue->enqueueFromDb(999);

        // Simply to avoid PHPUnit classifying this as a risky test
        $this->assertTrue(true);
    }

    /**
     * For each row dispatches to the correct transport
     * @return void
     */
    public function testEnqueueManyByRowsDispatchesForAllRows(): void
    {
        // Mocks, Repo is for constructor
        $repo = $this->createMock(TaskRepository::class); // not used in method
        $bus  = $this->createMock(MessageBusInterface::class);

        // Replace dispatch with a callback that pushes the message + stamps it into $captures
        // Returns a real Envelope (what bus usualy returns)
        $captures = [];
        $bus->method('dispatch')->willReturnCallback(function ($message, array $stamps = []) use (&$captures) {
            $captures[] = [$message, $stamps];
            return new Envelope($message, $stamps);
        });

        // Test data to hit every branch, low/normal/high/missing
        $rows = [
            ['id' => 1, 'priority' => 1],
            ['id' => 2, 'priority' => 2],
            ['id' => 3, 'priority' => 3],
            ['id' => 4, 'priority' => 99],
            ['id' => 5, 'priority' => 0],
            ['id' => 6],
        ];

        // Dispatches one message per row with transport derived from row's priority
        $queue = new TaskQueue($bus, $repo);
        $queue->enqueueManyByRows($rows);

        // Make sure 6 were dispatched
        $this->assertCount(6, $captures);

        // The expected transport names for each row in order
        $expected = [
            ['async_low'],
            ['async'],
            ['async_high'],
            ['async_high'],
            ['async_low'],
            ['async'],
        ];

        // Foreach captured dispatch
        foreach ($captures as $i => $cap) {
            // Extract the TransportNamesStamp
            $stamp = $this->extractTransportStamp($cap[1]);
            // Assert it matches the expected transport
            $this->assertSame($expected[$i], $stamp->getTransportNames(), "Row $i transport mismatch");
            // Assert the message type is correct
            $this->assertInstanceOf(TaskProcessMessage::class, $cap[0]);
        }
    }    
}
