<?php

namespace App\Tests\Functional;

use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class DispatchAllTest extends WebTestCase
{
    private EntityManagerInterface $em;
    private KernelBrowser $client;

    protected function setUp(): void
    {
        // Reset any previous Kernel state between tests
        self::ensureKernelShutdown(); 
        // Boots Kernel and gives us a BrowserKit client
        $this->client = self::createClient();

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

        // Create 3 tasks with 3 priorities
        foreach ([['A',1], ['B',2], ['C',3]] as [$name, $prio]) {
            $t = (new Task())
                ->setName($name)
                ->setStatus('pending')
                ->setPriority($prio)
                ->setRetryCount(0)
                ->setCreatedAt(new \DateTimeImmutable());
            $this->em->persist($t);
        }
        $this->em->flush();
    }

    /**
     * We test that the route "/tasks/dispatch-all" returns the expected Response
     * Enqueus messages per DB priority
     * Since our transport is "sync://" its processed immediatly by our handler
     * Check the tasks were processed correctly
     * @return void
     */
    public function testDispatchAllReturnsTextAndProcessesTasks(): void
    {
        // GET the /tasks/dispatch-all URL
        $this->client->request('GET', '/tasks/dispatch-all');

        // Assert status code is 2XX
        $this->assertResponseIsSuccessful();

        // Assert the response body is exactly the one expected
        $this->assertSame(
            'Success : Dispatched 3 tasks by DB priority.',
            trim($this->client->getResponse()->getContent())
        );

        // Detach all managed entities so next find hits the DB
        $this->em->clear();

        // Get all the tasks
        $all = $this->em->getRepository(\App\Entity\Task::class)->findAll();

        // Make sure we have 3 tasks
        $this->assertCount(3, $all);

        // For each task wheck the status and that processedAt is not null
        foreach ($all as $task) {
            $this->assertSame('processed', $task->getStatus());
            $this->assertInstanceOf(\DateTimeImmutable::class, $task->getProcessedAt());
        }
    }
}
