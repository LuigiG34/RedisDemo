<?php

namespace App\Controller;

use App\Enum\QueuePriority;
use App\Repository\TaskRepository;
use App\Service\TaskQueue;
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

    #[Route('/dispatch-all', name: 'tasks_dispatch_by_db', methods: ['POST','GET'])]
    public function dispatchAll(TaskRepository $repo, TaskQueue $queue): Response
    {
        $rows = $repo->createQueryBuilder('t')
            ->select('t.id AS id, t.priority AS priority')
            ->orderBy('t.id', 'ASC')
            ->getQuery()
            ->getScalarResult();

        $queue->enqueueManyByRows($rows);

        dd('success', sprintf('Dispatched %d tasks by DB priority.', \count($rows)));
    }
}
