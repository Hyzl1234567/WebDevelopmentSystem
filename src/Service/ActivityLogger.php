<?php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ActivityLogger
{
    private EntityManagerInterface $entityManager;
    private RequestStack $requestStack;

    public function __construct(EntityManagerInterface $entityManager, RequestStack $requestStack)
    {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
    }

    public function log(?User $user, string $action, ?string $entity = null, ?int $entityId = null, ?string $description = null): void
    {
        $log = new ActivityLog();
        $log->setUser($user);
        $log->setAction($action);
        $log->setEntity($entity);
        $log->setEntityId($entityId);
        $log->setDescription($description);
        $log->setCreatedAt(new \DateTimeImmutable());

        // Get IP address from request
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $log->setIpAddress($request->getClientIp());
        }

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    public function logLogin(User $user): void
    {
        $this->log(
            $user,
            'login',
            'User',
            $user->getId(),
            sprintf('User %s logged in', $user->getUsername())
        );
    }

    public function logLogout(User $user): void
    {
        $this->log(
            $user,
            'logout',
            'User',
            $user->getId(),
            sprintf('User %s logged out', $user->getUsername())
        );
    }

    // Helper methods for common operations
    public function logCreate(User $user, string $entity, int $entityId, string $entityName): void
    {
        $role = $this->getUserRole($user);
        $this->log(
            $user,
            'create',
            $entity,
            $entityId,
            sprintf('%s created %s: %s', $role, $entity, $entityName)
        );
    }

    public function logUpdate(User $user, string $entity, int $entityId, string $entityName): void
    {
        $role = $this->getUserRole($user);
        $this->log(
            $user,
            'update',
            $entity,
            $entityId,
            sprintf('%s updated %s: %s', $role, $entity, $entityName)
        );
    }

    public function logDelete(User $user, string $entity, int $entityId, string $entityName): void
    {
        $role = $this->getUserRole($user);
        $this->log(
            $user,
            'delete',
            $entity,
            $entityId,
            sprintf('%s deleted %s: %s', $role, $entity, $entityName)
        );
    }

    private function getUserRole(User $user): string
    {
        $roles = $user->getRoles();
        if (in_array('ROLE_ADMIN', $roles)) {
            return 'Admin';
        }
        if (in_array('ROLE_STAFF', $roles)) {
            return 'Staff';
        }
        return 'User';
    }
}