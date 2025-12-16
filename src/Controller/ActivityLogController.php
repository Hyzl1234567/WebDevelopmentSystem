<?php

namespace App\Controller;

use App\Entity\ActivityLog;
use App\Repository\ActivityLogRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/logs')]
#[IsGranted('ROLE_ADMIN')] // Only admins can access activity logs
class ActivityLogController extends AbstractController
{
    #[Route('', name: 'app_activity_log_index', methods: ['GET'])]
    public function index(Request $request, ActivityLogRepository $activityLogRepository, UserRepository $userRepository): Response
    {
        // Get filter parameters
        $userId = $request->query->get('user') ? (int)$request->query->get('user') : null;
        $action = $request->query->get('action');
        $entity = $request->query->get('entity');
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');

        // Convert user ID to User object
        $user = $userId ? $userRepository->find($userId) : null;
        
        // Convert dates to DateTimeImmutable
        $startDateObj = $startDate ? new \DateTimeImmutable($startDate) : null;
        $endDateObj = $endDate ? new \DateTimeImmutable($endDate) : null;

        // Build query with filters
        $queryBuilder = $activityLogRepository->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC');

        if ($user) {
            $queryBuilder->andWhere('a.user = :user')
                ->setParameter('user', $user);
        }

        if ($action) {
            $queryBuilder->andWhere('a.action = :action')
                ->setParameter('action', $action);
        }

        if ($entity) {
            $queryBuilder->andWhere('a.entity = :entity')
                ->setParameter('entity', $entity);
        }

        if ($startDateObj) {
            $queryBuilder->andWhere('a.createdAt >= :startDate')
                ->setParameter('startDate', $startDateObj);
        }

        if ($endDateObj) {
            // Set end date to end of day (23:59:59)
            $endOfDay = $endDateObj->setTime(23, 59, 59);
            $queryBuilder->andWhere('a.createdAt <= :endDate')
                ->setParameter('endDate', $endOfDay);
        }

        $logs = $queryBuilder->getQuery()->getResult();

        // Get all users for filter dropdown
        $users = $userRepository->findAll();

        // Get unique actions and entities
        $actions = $activityLogRepository->getUniqueActions();
        $entities = $activityLogRepository->getUniqueEntities();

        return $this->render('activity_log/index.html.twig', [
            'logs' => $logs,
            'users' => $users,
            'actions' => $actions,
            'entities' => $entities,
            'current_filters' => [
                'user' => $userId,
                'action' => $action,
                'entity' => $entity,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    #[Route('/{id}', name: 'app_activity_log_show', methods: ['GET'])]
    public function show(ActivityLog $log): Response
    {
        return $this->render('activity_log/show.html.twig', [
            'log' => $log,
        ]);
    }

    #[Route('/export/csv', name: 'app_activity_log_export', methods: ['GET'])]
    public function export(Request $request, ActivityLogRepository $activityLogRepository, UserRepository $userRepository): Response
    {
        // Get filter parameters (same as index)
        $userId = $request->query->get('user') ? (int)$request->query->get('user') : null;
        $action = $request->query->get('action');
        $entity = $request->query->get('entity');
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');

        // Convert to proper types
        $user = $userId ? $userRepository->find($userId) : null;
        $startDateObj = $startDate ? new \DateTimeImmutable($startDate) : null;
        $endDateObj = $endDate ? new \DateTimeImmutable($endDate) : null;

        // Build query with same filters
        $queryBuilder = $activityLogRepository->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC');

        if ($user) {
            $queryBuilder->andWhere('a.user = :user')->setParameter('user', $user);
        }
        if ($action) {
            $queryBuilder->andWhere('a.action = :action')->setParameter('action', $action);
        }
        if ($entity) {
            $queryBuilder->andWhere('a.entity = :entity')->setParameter('entity', $entity);
        }
        if ($startDateObj) {
            $queryBuilder->andWhere('a.createdAt >= :startDate')
                ->setParameter('startDate', $startDateObj);
        }
        if ($endDateObj) {
            $endOfDay = $endDateObj->setTime(23, 59, 59);
            $queryBuilder->andWhere('a.createdAt <= :endDate')
                ->setParameter('endDate', $endOfDay);
        }

        $logs = $queryBuilder->getQuery()->getResult();

        // Create CSV
        $csv = "ID,User ID,Username,Role,Action,Entity,Entity ID,Description,IP Address,Timestamp\n";
        
        foreach ($logs as $log) {
            $user = $log->getUser();
            $userId = $user ? $user->getId() : 'N/A';
            $username = $user ? $user->getUsername() : 'System';
            $role = $user && in_array('ROLE_ADMIN', $user->getRoles()) ? 'Admin' : 'Staff';
            
            $csv .= sprintf(
                "%d,%s,%s,%s,%s,%s,%s,\"%s\",%s,%s\n",
                $log->getId(),
                $userId,
                $username,
                $role,
                $log->getAction(),
                $log->getEntity() ?? 'N/A',
                $log->getEntityId() ?? 'N/A',
                str_replace('"', '""', $log->getDescription() ?? ''),
                $log->getIpAddress() ?? 'N/A',
                $log->getCreatedAt() ? $log->getCreatedAt()->format('Y-m-d H:i:s') : 'N/A'
            );
        }

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="activity_logs_' . date('Y-m-d_His') . '.csv"');

        return $response;
    }
}