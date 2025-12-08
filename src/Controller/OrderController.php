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

#[Route('/order')]
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
        return $this->render('order/index.html.twig', [
            'orders' => $orderRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_order_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $order = new Order();
        $form = $this->createForm(Order1Type::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($order);
            $entityManager->flush();

            // Log the activity - Staff creates a record
            $this->activityLogger->logCreate(
                $this->getUser(),
                'Order',
                $order->getId(),
                sprintf(
                    '#%d - Customer: %s, Product: %s, Total: ₱%s',
                    $order->getId(),
                    $order->getCustomer(),
                    $order->getProduct()->getName(),
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
        $form = $this->createForm(Order1Type::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            // Log the activity - Staff edits a record
            $this->activityLogger->logUpdate(
                $this->getUser(),
                'Order',
                $order->getId(),
                sprintf(
                    '#%d - Customer: %s',
                    $order->getId(),
                    $order->getCustomer()
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
        if ($this->isCsrfTokenValid('delete'.$order->getId(), $request->getPayload()->getString('_token'))) {
            // Store data before deletion
            $orderId = $order->getId();
            $customerName = $order->getCustomer();
            $productName = $order->getProduct()->getName();

            $entityManager->remove($order);
            $entityManager->flush();

            // Log the activity - Staff deletes a record
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
}