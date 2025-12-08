<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class UserManagementController extends AbstractController
{
    #[Route('/', name: 'app_user_management_index')]
    public function index(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->render('user_management/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/new', name: 'app_user_management_new')]
    public function new(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager, ActivityLogger $activityLogger): Response
    {
        if ($request->isMethod('POST')) {
            $user = new User();
            $user->setCreatedAt(new \DateTimeImmutable());
            $user->setUsername($request->request->get('username'));
            $user->setEmail($request->request->get('email'));
            $user->setFullName($request->request->get('full_name'));
            
            $role = $request->request->get('role');
            if ($role === 'ROLE_ADMIN') {
                $user->setRoles(['ROLE_ADMIN']);
            } elseif ($role === 'ROLE_STAFF') {
                $user->setRoles(['ROLE_STAFF']);
            } else {
                $user->setRoles(['ROLE_USER']);
            }

            $password = $request->request->get('password');
            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);
            $user->setStatus('active');

            $entityManager->persist($user);
            $entityManager->flush();

            // LOG: Admin creates a user
            $activityLogger->log(
                $this->getUser(),
                'create',
                'User',
                $user->getId(),
                sprintf('Admin created user: %s with role: %s', $user->getUsername(), $role)
            );

            $this->addFlash('success', 'User created successfully!');
            return $this->redirectToRoute('app_user_management_index');
        }

        return $this->render('user_management/new.html.twig');
    }

    #[Route('/{id}/edit', name: 'app_user_management_edit')]
    public function edit(User $user, Request $request, EntityManagerInterface $entityManager, ActivityLogger $activityLogger): Response
    {
        if ($request->isMethod('POST')) {
            $oldRole = $user->getRoles()[0] ?? 'ROLE_USER';
            $oldStatus = $user->getStatus();
            
            $user->setUsername($request->request->get('username'));
            $user->setEmail($request->request->get('email'));
            $user->setFullName($request->request->get('full_name'));
            
            $role = $request->request->get('role');
            if ($role === 'ROLE_ADMIN') {
                $user->setRoles(['ROLE_ADMIN']);
            } elseif ($role === 'ROLE_STAFF') {
                $user->setRoles(['ROLE_STAFF']);
            } else {
                $user->setRoles(['ROLE_USER']);
            }

            $newStatus = $request->request->get('status');
            $user->setStatus($newStatus);

            $entityManager->flush();

            // LOG: Admin updates a user
            $changes = [];
            if ($oldRole !== $role) {
                $changes[] = sprintf('role: %s → %s', $oldRole, $role);
            }
            if ($oldStatus !== $newStatus) {
                $changes[] = sprintf('status: %s → %s', $oldStatus, $newStatus);
            }
            
            $changeDescription = !empty($changes) ? ' (' . implode(', ', $changes) . ')' : '';
            
            $activityLogger->log(
                $this->getUser(),
                'update',
                'User',
                $user->getId(),
                sprintf('Admin updated user: %s%s', $user->getUsername(), $changeDescription)
            );

            $this->addFlash('success', 'User updated successfully!');
            return $this->redirectToRoute('app_user_management_index');
        }

        return $this->render('user_management/edit.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/reset-password', name: 'app_user_management_reset_password')]
    public function resetPassword(User $user, Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager, ActivityLogger $activityLogger): Response
    {
        if ($request->isMethod('POST')) {
            $newPassword = $request->request->get('new_password');
            $confirmPassword = $request->request->get('confirm_password');

            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'Passwords do not match!');
                return $this->redirectToRoute('app_user_management_reset_password', ['id' => $user->getId()]);
            }

            if (strlen($newPassword) < 6) {
                $this->addFlash('error', 'Password must be at least 6 characters!');
                return $this->redirectToRoute('app_user_management_reset_password', ['id' => $user->getId()]);
            }

            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
            $entityManager->flush();

            // LOG: Admin resets password
            $activityLogger->log(
                $this->getUser(),
                'update',
                'User',
                $user->getId(),
                sprintf('Admin reset password for user: %s', $user->getUsername())
            );

            $this->addFlash('success', 'Password reset successfully!');
            return $this->redirectToRoute('app_user_management_index');
        }

        return $this->render('user_management/reset_password.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/toggle-status', name: 'app_user_management_toggle_status')]
    public function toggleStatus(User $user, EntityManagerInterface $entityManager, ActivityLogger $activityLogger): Response
    {
        $oldStatus = $user->getStatus();
        
        if ($user->getStatus() === 'active') {
            $user->setStatus('disabled');
            $message = 'User account disabled.';
            $description = sprintf('Admin disabled user account: %s (status: active → disabled)', $user->getUsername());
        } else {
            $user->setStatus('active');
            $message = 'User account activated.';
            $description = sprintf('Admin activated user account: %s (status: %s → active)', $user->getUsername(), $oldStatus);
        }

        $entityManager->flush();

        // LOG: Admin updates status
        $activityLogger->log(
            $this->getUser(),
            'update',
            'User',
            $user->getId(),
            $description
        );

        $this->addFlash('success', $message);
        return $this->redirectToRoute('app_user_management_index');
    }

    #[Route('/{id}/archive', name: 'app_user_management_archive')]
    public function archive(User $user, EntityManagerInterface $entityManager, ActivityLogger $activityLogger): Response
    {
        $oldStatus = $user->getStatus();
        $user->setStatus('archived');
        $entityManager->flush();

        // LOG: Admin archives user
        $activityLogger->log(
            $this->getUser(),
            'update',
            'User',
            $user->getId(),
            sprintf('Admin archived user account: %s (status: %s → archived)', $user->getUsername(), $oldStatus)
        );

        $this->addFlash('success', 'User account archived.');
        return $this->redirectToRoute('app_user_management_index');
    }

    #[Route('/{id}/delete', name: 'app_user_management_delete', methods: ['POST'])]
    public function delete(User $user, EntityManagerInterface $entityManager, ActivityLogger $activityLogger): Response
    {
        $username = $user->getUsername();
        $userId = $user->getId();
        $userRole = $user->getRoles()[0] ?? 'ROLE_USER';

        // LOG BEFORE DELETION: Admin deletes a user
        $activityLogger->log(
            $this->getUser(),
            'delete',
            'User',
            $userId,
            sprintf('Admin deleted user account: %s (role: %s)', $username, $userRole)
        );

        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('success', 'User deleted successfully!');
        return $this->redirectToRoute('app_user_management_index');
    }
}