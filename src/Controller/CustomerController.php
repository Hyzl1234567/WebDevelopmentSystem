<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Form\CustomerType;
use App\Repository\CustomerRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/customer')]
#[IsGranted('ROLE_USER')] // Only Staff and Admin can access
final class CustomerController extends AbstractController
{
    private ActivityLogger $activityLogger;

    public function __construct(ActivityLogger $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    #[Route(name: 'app_customer_index', methods: ['GET'])]
    public function index(CustomerRepository $customerRepository): Response
    {
        return $this->render('customer/index.html.twig', [
            'customers' => $customerRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_customer_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $customer = new Customer();
        $form = $this->createForm(CustomerType::class, $customer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $customer->setCreatedBy($this->getUser());
            
            $entityManager->persist($customer);
            $entityManager->flush();

            $this->activityLogger->logCreate(
                $this->getUser(),
                'Customer',
                $customer->getId(),
                sprintf(
                    '%s - Email: %s, Phone: %s',
                    $customer->getName(),
                    $customer->getEmail() ?? 'N/A',
                    $customer->getPhone() ?? 'N/A'
                )
            );

            $this->addFlash('success', 'Customer created successfully!');

            return $this->redirectToRoute('app_customer_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('customer/new.html.twig', [
            'customer' => $customer,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_customer_show', methods: ['GET'])]
    public function show(Customer $customer): Response
    {
        return $this->render('customer/show.html.twig', [
            'customer' => $customer,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_customer_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Customer $customer, EntityManagerInterface $entityManager): Response
    {
        // Both ADMIN and STAFF have full access to edit any customer
        if (!$this->canEditOrDelete($customer)) {
            $this->addFlash('error', 'You do not have permission to edit this customer. You need staff or admin privileges.');
            return $this->redirectToRoute('app_customer_index', [], Response::HTTP_SEE_OTHER);
        }

        $form = $this->createForm(CustomerType::class, $customer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->activityLogger->logUpdate(
                $this->getUser(),
                'Customer',
                $customer->getId(),
                sprintf('%s', $customer->getName())
            );

            $this->addFlash('success', 'Customer updated successfully!');

            return $this->redirectToRoute('app_customer_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('customer/edit.html.twig', [
            'customer' => $customer,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_customer_delete', methods: ['POST'])]
    public function delete(Request $request, Customer $customer, EntityManagerInterface $entityManager): Response
    {
        // Both ADMIN and STAFF have full access to delete any customer
        if (!$this->canEditOrDelete($customer)) {
            $this->addFlash('error', 'You do not have permission to delete this customer. You need staff or admin privileges.');
            return $this->redirectToRoute('app_customer_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($this->isCsrfTokenValid('delete'.$customer->getId(), $request->getPayload()->getString('_token'))) {
            // Check if customer has orders
            if ($customer->getOrders()->count() > 0) {
                $this->addFlash('error', sprintf(
                    'Cannot delete customer "%s" because they have %d order(s). Please delete or reassign their orders first.',
                    $customer->getName(),
                    $customer->getOrders()->count()
                ));
                return $this->redirectToRoute('app_customer_index', [], Response::HTTP_SEE_OTHER);
            }

            $customerId = $customer->getId();
            $customerName = $customer->getName();

            $entityManager->remove($customer);
            $entityManager->flush();

            $this->activityLogger->logDelete(
                $this->getUser(),
                'Customer',
                $customerId,
                sprintf('Customer: %s', $customerName)
            );

            $this->addFlash('success', 'Customer deleted successfully!');
        }

        return $this->redirectToRoute('app_customer_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Check if current user can edit or delete a customer
     * Both ADMIN and STAFF have full access to all customers
     */
    private function canEditOrDelete(Customer $customer): bool
    {
        $currentUser = $this->getUser();
        
        // Allow if user is ADMIN or STAFF
        if (in_array('ROLE_ADMIN', $currentUser->getRoles()) || 
            in_array('ROLE_STAFF', $currentUser->getRoles())) {
            return true;
        }

        // Regular users cannot edit/delete
        return false;
    }
}