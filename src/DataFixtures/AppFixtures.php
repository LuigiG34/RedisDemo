<?php

namespace App\DataFixtures;

use App\Entity\Task;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 50; $i++) {
            $task = new Task();
            $task->setName('Task ' . $i);
            $task->setStatus('pending');
            $task->setPriority($i % 3 + 1); 
            $task->setCreatedAt(new \DateTimeImmutable());
            $task->setProcessedAt($i < 5 ? new \DateTimeImmutable('-' . rand(1, 10) . ' days') : null); // Quelques tâches traitées
            $task->setDescription($i % 2 ? 'Description pour Task ' . $i : null);
            $task->setAssignedTo($i % 4 ? 'User' . ($i % 4) : null);
            $task->setRetryCount(rand(0, 2));
            $manager->persist($task);
        }
        $manager->flush();

        $manager->flush();
    }
}
