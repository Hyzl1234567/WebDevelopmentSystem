<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/product')]
#[IsGranted('ROLE_USER')] // Staff and Admin can access
final class ProductController extends AbstractController
{
    private ActivityLogger $activityLogger;

    public function __construct(ActivityLogger $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    #[Route(name: 'app_product_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        return $this->render('product/index.html.twig', [
            'products' => $productRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // Handle exception
                }

                $product->setImage($newFilename);
            }

            // Set who created this product
            $product->setCreatedBy($this->getUser());

            $entityManager->persist($product);
            $entityManager->flush();

            // Log the activity
            $this->activityLogger->logCreate(
                $this->getUser(),
                'Product',
                $product->getId(),
                sprintf(
                    '#%d - %s (₱%s)',
                    $product->getId(),
                    $product->getName(),
                    number_format($product->getPrice(), 2)
                )
            );

            $this->addFlash('success', 'Product created successfully!');
            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        // Check if user can edit this product
        if (!$this->canEditOrDelete($product)) {
            $this->addFlash('error', 'You do not have permission to edit this product. You can only edit your own records.');
            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        $oldImage = $product->getImage();
        
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // Handle the error if needed
                }

                $product->setImage($newFilename);
            }

            $entityManager->flush();

            // Log the activity
            $this->activityLogger->logUpdate(
                $this->getUser(),
                'Product',
                $product->getId(),
                sprintf(
                    '#%d - %s',
                    $product->getId(),
                    $product->getName()
                )
            );

            $this->addFlash('success', 'Product updated successfully!');
            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        // Check if user can delete this product
        if (!$this->canEditOrDelete($product)) {
            $this->addFlash('error', 'You do not have permission to delete this product. You can only delete your own records.');
            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->getPayload()->getString('_token'))) {
            
            // Check if product has orders
            if ($product->getOrders()->count() > 0) {
                $this->addFlash('error', sprintf(
                    '❌ Cannot delete product "%s" because it has %d order(s) associated with it. Orders must be kept for record-keeping purposes.',
                    $product->getName(),
                    $product->getOrders()->count()
                ));
                return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
            }
            
            // Check if product has sales
            if ($product->getSales()->count() > 0) {
                $this->addFlash('error', sprintf(
                    '❌ Cannot delete product "%s" because it has %d sales record(s). Sales data must be preserved for business analytics.',
                    $product->getName(),
                    $product->getSales()->count()
                ));
                return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
            }

            // Check if product has stocks
            if ($product->getStocks()->count() > 0) {
                $this->addFlash('error', sprintf(
                    '❌ Cannot delete product "%s" because it has %d stock record(s). Please delete the stock entries first.',
                    $product->getName(),
                    $product->getStocks()->count()
                ));
                return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
            }

            // If we reach here, it's safe to delete
            $productName = $product->getName();
            $productId = $product->getId();
            $productPrice = $product->getPrice();

            // Log before deletion
            $this->activityLogger->logDelete(
                $this->getUser(),
                'Product',
                $productId,
                sprintf(
                    '#%d - %s (₱%s)',
                    $productId,
                    $productName,
                    number_format($productPrice, 2)
                )
            );

            $entityManager->remove($product);
            $entityManager->flush();

            $this->addFlash('success', sprintf('✅ Product "%s" deleted successfully!', $productName));
        }

        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Check if the current user can edit or delete the product
     * - Admin and Staff have full access to all records
     */
    private function canEditOrDelete(Product $product): bool
    {
        $currentUser = $this->getUser();
        
        // If no creator is set, allow access (for legacy records)
        if (!$product->getCreatedBy()) {
            return true;
        }

        // Both ADMIN and STAFF have full access
        if (in_array('ROLE_ADMIN', $currentUser->getRoles()) || in_array('ROLE_STAFF', $currentUser->getRoles())) {
            return true;
        }

        return false;
    }
}