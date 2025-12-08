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
#[IsGranted('ROLE_STAFF')] // Ensure only staff/admin can access
final class ProductController extends AbstractController
{
    #[Route(name: 'app_product_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        return $this->render('product/index.html.twig', [
            'products' => $productRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, ActivityLogger $activityLogger): Response
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

            $entityManager->persist($product);
            $entityManager->flush();

            // LOG: Staff/Admin creates a product
            $activityLogger->log(
                $this->getUser(),
                'create',
                'Product',
                $product->getId(),
                sprintf('%s created product: %s (Price: $%.2f)', 
                    in_array('ROLE_ADMIN', $this->getUser()->getRoles()) ? 'Admin' : 'Staff',
                    $product->getName(),
                    $product->getPrice()
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
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager, SluggerInterface $slugger, ActivityLogger $activityLogger): Response
    {
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

            // LOG: Staff/Admin edits a product
            $activityLogger->log(
                $this->getUser(),
                'update',
                'Product',
                $product->getId(),
                sprintf('%s updated product: %s (New Price: $%.2f)', 
                    in_array('ROLE_ADMIN', $this->getUser()->getRoles()) ? 'Admin' : 'Staff',
                    $product->getName(),
                    $product->getPrice()
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
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager, ActivityLogger $activityLogger): Response
    {
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->getPayload()->getString('_token'))) {
            $productName = $product->getName();
            $productId = $product->getId();
            $productPrice = $product->getPrice();

            // LOG BEFORE DELETION: Staff/Admin deletes a product
            $activityLogger->log(
                $this->getUser(),
                'delete',
                'Product',
                $productId,
                sprintf('%s deleted product: %s (Price: $%.2f)', 
                    in_array('ROLE_ADMIN', $this->getUser()->getRoles()) ? 'Admin' : 'Staff',
                    $productName,
                    $productPrice
                )
            );

            $entityManager->remove($product);
            $entityManager->flush();

            $this->addFlash('success', 'Product deleted successfully!');
        }

        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }
}