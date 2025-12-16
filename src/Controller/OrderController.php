<?php

namespace App\Controller;

use App\Entity\Order;
use App\Form\Order1Type;
use App\Repository\OrderRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/order')]
#[IsGranted('ROLE_USER')]
final class OrderController extends AbstractController
{
    private ActivityLogger $activityLogger;

    public function __construct(ActivityLogger $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    #[Route(name: 'app_order_index', methods: ['GET'])]
    public function index(OrderRepository $orderRepository): Response
    {
        // Use an eager-loading repository method to avoid Doctrine trying to lazy-load
        // related entities that may have been deleted (which causes EntityNotFound exceptions).
        return $this->render('order/index.html.twig', [
            'orders' => $orderRepository->findAllWithRelations(),
        ]);
    }

    #[Route('/new', name: 'app_order_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $order = new Order();
        $form = $this->createForm(Order1Type::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $order->setCreatedBy($this->getUser());
            
            $entityManager->persist($order);
            $entityManager->flush();

            $this->activityLogger->logCreate(
                $this->getUser(),
                'Order',
                $order->getId(),
                sprintf(
                    '#%d - Customer: %s, Product: %s, Quantity: %d, Total: ₱%s',
                    $order->getId(),
                    $order->getCustomer() ? $order->getCustomer()->getName() : 'Deleted Customer',
                    $order->getProduct() ? $order->getProduct()->getName() : 'Deleted Product',
                    $order->getQuantity(),
                    number_format($order->getTotalPrice(), 2)
                )
            );

            $this->addFlash('success', 'Order created successfully!');

            return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('order/new.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_order_show', methods: ['GET'])]
    public function show(Order $order): Response
    {
        return $this->render('order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_order_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        if (!$this->canEditOrDelete($order)) {
            $this->addFlash('error', 'You do not have permission to edit this order. You can only edit your own records.');
            return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
        }

        $form = $this->createForm(Order1Type::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->activityLogger->logUpdate(
                $this->getUser(),
                'Order',
                $order->getId(),
                sprintf(
                    '#%d - Customer: %s',
                    $order->getId(),
                    $order->getCustomer() ? $order->getCustomer()->getName() : 'Deleted Customer'
                )
            );

            $this->addFlash('success', 'Order updated successfully!');

            return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('order/edit.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_order_delete', methods: ['POST'])]
    public function delete(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        if (!$this->canEditOrDelete($order)) {
            $this->addFlash('error', 'You do not have permission to delete this order. You can only delete your own records.');
            return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($this->isCsrfTokenValid('delete'.$order->getId(), $request->getPayload()->getString('_token'))) {
            $orderId = $order->getId();
            $customerName = $order->getCustomer() ? $order->getCustomer()->getName() : 'Deleted Customer';
            $productName = $order->getProduct() ? $order->getProduct()->getName() : 'Deleted Product';

            $entityManager->remove($order);
            $entityManager->flush();

            $this->activityLogger->logDelete(
                $this->getUser(),
                'Order',
                $orderId,
                sprintf(
                    '#%d - Customer: %s, Product: %s',
                    $orderId,
                    $customerName,
                    $productName
                )
            );

            $this->addFlash('success', 'Order deleted successfully!');
        }

        return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
    }

    private function canEditOrDelete(Order $order): bool
    {
        $currentUser = $this->getUser();
        
        if (!$order->getCreatedBy()) {
            return true;
        }

        // Both ADMIN and STAFF have full access
        if (in_array('ROLE_ADMIN', $currentUser->getRoles()) || in_array('ROLE_STAFF', $currentUser->getRoles())) {
            return true;
        }

        return false;
    }
}