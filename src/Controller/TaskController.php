<?php

namespace App\Controller;

use App\Service\TaskReadModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tasks')]
final class TaskController extends AbstractController
{
    #[Route('', name: 'tasks_index', methods: ['GET'])]
    public function index(TaskReadModel $read): Response
    {
        [$tasks, $source] = $read->listWithSource();

        $this->addFlash('info', $source === 'db'
            ? 'Data loaded from DB and cached in Redis'
            : 'Data loaded from Redis cache'
        );

        return $this->render('task/index.html.twig', [
            'tasks' => $tasks,
        ]);
    }
}
