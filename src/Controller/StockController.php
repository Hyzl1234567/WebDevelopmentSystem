<?php

namespace App\Controller;

use App\Entity\Stock;
use App\Form\StockType;
use App\Repository\StockRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/stock')]
#[IsGranted('ROLE_USER')] // Staff and Admin can access
class StockController extends AbstractController
{
    private ActivityLogger $activityLogger;

    public function __construct(ActivityLogger $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    #[Route('/', name: 'app_stock_index', methods: ['GET'])]
    public function index(Request $request, StockRepository $stockRepository): Response
    {
        $query = $request->query->get('q');

        if ($query) {
            $stocks = $stockRepository->createQueryBuilder('s')
                ->join('s.product', 'p')
                ->where('p.name LIKE :query OR s.id LIKE :query')
                ->setParameter('query', '%' . $query . '%')
                ->getQuery()
                ->getResult();
        } else {
            $stocks = $stockRepository->findAll();
        }

        return $this->render('stock/index.html.twig', [
            'stocks' => $stocks,
        ]);
    }

    #[Route('/new', name: 'app_stock_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $stock = new Stock();
        $form = $this->createForm(StockType::class, $stock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Set who created this stock record
            $stock->setCreatedBy($this->getUser());
            
            $entityManager->persist($stock);
            $entityManager->flush();

            // 🟢 Recalculate total stock quantity for related product
            $product = $stock->getProduct();
            if ($product) {
                $totalStock = $entityManager->getRepository(Stock::class)
                    ->createQueryBuilder('s')
                    ->select('SUM(s.quantity)')
                    ->where('s.product = :product')
                    ->setParameter('product', $product)
                    ->getQuery()
                    ->getSingleScalarResult();

                $product->setQuantity((int)($totalStock ?? 0));
                $entityManager->persist($product);
                $entityManager->flush();
            }

            // Log the activity
            $this->activityLogger->logCreate(
                $this->getUser(),
                'Stock',
                $stock->getId(),
                sprintf(
                    '#%d - Product: %s, Qty: %d',
                    $stock->getId(),
                    $product ? $product->getName() : 'Unknown',
                    $stock->getQuantity()
                )
            );

            $this->addFlash('success', 'Stock added successfully!');
            return $this->redirectToRoute('app_stock_index');
        }

        return $this->render('stock/new.html.twig', [
            'stock' => $stock,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_stock_show', methods: ['GET'])]
    public function show(Stock $stock): Response
    {
        return $this->render('stock/show.html.twig', [
            'stock' => $stock,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_stock_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Stock $stock, EntityManagerInterface $entityManager, StockRepository $stockRepository): Response
    {
        // Check if user can edit this stock record
        if (!$this->canEditOrDelete($stock)) {
            $this->addFlash('error', 'You do not have permission to edit this stock record. You can only edit your own records.');
            return $this->redirectToRoute('app_stock_index');
        }

        $oldQuantity = $stock->getQuantity();
        
        $form = $this->createForm(StockType::class, $stock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newQuantity = $stock->getQuantity();
            
            $entityManager->flush();

            // 🟢 Recalculate total stock for the related product
            $product = $stock->getProduct();
            if ($product) {
                $totalStock = $stockRepository->createQueryBuilder('s')
                    ->select('SUM(s.quantity)')
                    ->where('s.product = :product')
                    ->setParameter('product', $product)
                    ->getQuery()
                    ->getSingleScalarResult();

                $product->setQuantity((int)($totalStock ?? 0));
                $entityManager->persist($product);
                $entityManager->flush();
            }

            // Log the activity
            $this->activityLogger->logUpdate(
                $this->getUser(),
                'Stock',
                $stock->getId(),
                sprintf(
                    '#%d - Product: %s (Qty: %d → %d)',
                    $stock->getId(),
                    $product ? $product->getName() : 'Unknown',
                    $oldQuantity,
                    $newQuantity
                )
            );

            $this->addFlash('success', 'Stock updated successfully!');
            return $this->redirectToRoute('app_stock_index');
        }

        return $this->render('stock/edit.html.twig', [
            'stock' => $stock,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_stock_delete', methods: ['POST'])]
    public function delete(Request $request, Stock $stock, EntityManagerInterface $entityManager): Response
    {
        // Check if user can delete this stock record
        if (!$this->canEditOrDelete($stock)) {
            $this->addFlash('error', 'You do not have permission to delete this stock record. You can only delete your own records.');
            return $this->redirectToRoute('app_stock_index');
        }

        if ($this->isCsrfTokenValid('delete' . $stock->getId(), $request->request->get('_token'))) {
            $product = $stock->getProduct();
            $stockQuantity = $stock->getQuantity();
            $stockId = $stock->getId();
            $productName = $product ? $product->getName() : 'Unknown';

            // Log before deletion
            $this->activityLogger->logDelete(
                $this->getUser(),
                'Stock',
                $stockId,
                sprintf(
                    '#%d - Product: %s, Qty: %d',
                    $stockId,
                    $productName,
                    $stockQuantity
                )
            );

            // 🟢 Remove the stock entry
            $entityManager->remove($stock);
            $entityManager->flush();

            // 🟢 Recalculate total stock for the related product
            if ($product) {
                $totalStock = $entityManager->getRepository(Stock::class)
                    ->createQueryBuilder('s')
                    ->select('SUM(s.quantity)')
                    ->where('s.product = :product')
                    ->setParameter('product', $product)
                    ->getQuery()
                    ->getSingleScalarResult();

                $product->setQuantity((int)($totalStock ?? 0));
                $entityManager->persist($product);
                $entityManager->flush();
            }

            $this->addFlash('success', 'Stock deleted successfully!');
        }

        return $this->redirectToRoute('app_stock_index');
    }

    /**
     * Check if the current user can edit or delete the stock record
     * - Admin and Staff have full access to all records
     */
    private function canEditOrDelete(Stock $stock): bool
    {
        $currentUser = $this->getUser();
        
        // If no creator is set, allow access (for legacy records)
        if (!$stock->getCreatedBy()) {
            return true;
        }

        // Both ADMIN and STAFF have full access
        if (in_array('ROLE_ADMIN', $currentUser->getRoles()) || in_array('ROLE_STAFF', $currentUser->getRoles())) {
            return true;
        }

        return false;
    }
}