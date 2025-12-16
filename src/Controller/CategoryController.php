<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/category')]
#[IsGranted('ROLE_USER')] // Staff and Admin can access
final class CategoryController extends AbstractController
{
    private ActivityLogger $activityLogger;

    public function __construct(ActivityLogger $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    #[Route(name: 'app_category_index', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepository): Response
    {
        return $this->render('category/index.html.twig', [
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Set who created this category
            $category->setCreatedBy($this->getUser());
            
            $entityManager->persist($category);
            $entityManager->flush();

            // Log the activity
            $this->activityLogger->logCreate(
                $this->getUser(),
                'Category',
                $category->getId(),
                sprintf('#%d - %s', $category->getId(), $category->getName())
            );

            $this->addFlash('success', 'Category created successfully!');
            return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('category/new.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_category_show', methods: ['GET'])]
    public function show(Category $category): Response
    {
        return $this->render('category/show.html.twig', [
            'category' => $category,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        // Check if user can edit this category
        if (!$this->canEditOrDelete($category)) {
            $this->addFlash('error', 'You do not have permission to edit this category. You can only edit your own records.');
            return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
        }

        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            // Log the activity
            $this->activityLogger->logUpdate(
                $this->getUser(),
                'Category',
                $category->getId(),
                sprintf('#%d - %s', $category->getId(), $category->getName())
            );

            $this->addFlash('success', 'Category updated successfully!');
            return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('category/edit.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_category_delete', methods: ['POST'])]
    public function delete(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        // Check if user can delete this category
        if (!$this->canEditOrDelete($category)) {
            $this->addFlash('error', 'You do not have permission to delete this category. You can only delete your own records.');
            return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->getPayload()->getString('_token'))) {
            $categoryName = $category->getName();
            $categoryId = $category->getId();

            // Log before deletion
            $this->activityLogger->logDelete(
                $this->getUser(),
                'Category',
                $categoryId,
                sprintf('#%d - %s', $categoryId, $categoryName)
            );

            $entityManager->remove($category);
            $entityManager->flush();

            $this->addFlash('success', 'Category deleted successfully!');
        }

        return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Check if the current user can edit or delete the category
     * - Admin can edit/delete all records
     * - Staff can only edit/delete their own records
     */
    private function canEditOrDelete(Category $category): bool
    {
        $currentUser = $this->getUser();
        
        // If no creator is set, allow access (for legacy records)
        if (!$category->getCreatedBy()) {
            return true;
        }

        // Admin can edit/delete everything
        if (in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            return true;
        }

        // Staff can only edit/delete their own records
        if (in_array('ROLE_STAFF', $currentUser->getRoles())) {
            return $category->getCreatedBy() === $currentUser;
        }

        return false;
    }
}