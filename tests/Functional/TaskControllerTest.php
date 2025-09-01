<?php

namespace App\Tests\Functional;

use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

// final class TaskControllerTest extends WebTestCase
// {
//     private EntityManagerInterface $em;
//     private KernelBrowser $client;


//     protected function setUp(): void
//     {
//         // Reset any previous Kernel state between tests
//         self::ensureKernelShutdown(); 
//         // Boot kernel & create a single BrowserKit client for this test
//         $this->client = self::createClient();
//         // Keep the same kernel across
//         $this->client->disableReboot();

//         // Fetch entity manager so we can create tables and insert data
//         $this->em = self::getContainer()->get(EntityManagerInterface::class);

//         // Clear the cache
//         self::getContainer()->get('cache.task')->clear();

//         // Wipe the database clean and create the tables
//         // Garantees each test runs on a fresh DB
//         $tool = new SchemaTool($this->em);
//         $meta = $this->em->getMetadataFactory()->getAllMetadata();
//         $tool->dropDatabase();
//         if ($meta) {
//             $tool->createSchema($meta);
//         }

//         // Create a couple of tasks
//         foreach ([['A', 1, 'pending'], ['B', 2, 'done']] as [$name, $prio, $status]) {
//             $t = (new Task())
//                 ->setName($name)
//                 ->setPriority($prio)
//                 ->setStatus($status)
//                 ->setRetryCount(0)
//                 ->setCreatedAt(new \DateTimeImmutable());
//             $this->em->persist($t);
//         }
//         $this->em->flush();
//     }

//     /**
//      * We test that the route "/tasks" returns the expected Response
//      * Test that on our first call we get the data from our DB then store it in Redis
//      * Test that our data on our second call is retrieved from Redis cache
//      * @return void
//      */
//     public function testIndexShowsDbThenCache(): void
//     {
//         // First call to "/tasks"
//         $this->client->request('GET', '/tasks');
//         // Assert status code is 2XX
//         $this->assertResponseIsSuccessful();
//         // Capture the HTML
//         $html1 = $this->client->getResponse()->getContent();
//         // Check that the page title exists
//         $this->assertStringContainsString('Redis Demo - Task List', $html1);
//         // Check the flash message telling us that the data was retrieved from our DB and then cached in Redis
//         $this->assertStringContainsString('Data loaded from DB and cached in Redis', $html1);

//         // Second call to "/tasks"
//         $this->client->request('GET', '/tasks');
//         // Assert status code is 2XX
//         $this->assertResponseIsSuccessful();
//         // Capture the HTML
//         $html2 = $this->client->getResponse()->getContent();
//         // Check the flash message telling us that the data was retrieved from Redis cache
//         $this->assertStringContainsString('Data loaded from Redis cache', $html2);
//     }

//     public function testCacheInvalidatesAfterTaskUpdate(): void
//     {
//         // First call to "/tasks"
//         $this->client->request('GET', '/tasks');
//         // Assert status code is 2XX
//         $this->assertResponseIsSuccessful();

//         // We update a Task and flush it to trigger the cache invalidation
//         $task = $this->em->getRepository(Task::class)->findOneBy(['name' => 'A']);
//         $this->assertNotNull($task);
//         $task->setStatus('done');
//         $this->em->flush();

//         // Second call to "/tasks"
//         $this->client->request('GET', '/tasks');
//         // Assert status code is 2XX
//         $this->assertResponseIsSuccessful();
//         // The data should be loaded from the DB because we invalidated the cache with ou previous update
//         $this->assertStringContainsString('Data loaded from DB and cached in Redis', $this->client->getResponse()->getContent());
//     }
// }
