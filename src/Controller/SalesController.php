<?php

namespace App\Controller;

use App\Repository\SalesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/sales')]
final class SalesController extends AbstractController
{
    #[Route('/', name: 'app_sales_index', methods: ['GET'])]
    public function index(SalesRepository $salesRepository): Response
    {
        // Fetch all sales
        $sales = $salesRepository->findAll();

        // Calculate total revenue
        $totalRevenue = 0;
        foreach ($sales as $sale) {
            $totalRevenue += $sale->getTotalAmount();
        }

        // Count total number of sales
        $totalSales = count($sales);

        return $this->render('sales/index.html.twig', [
            'sales' => $sales,
            'totalRevenue' => $totalRevenue,
            'totalSales' => $totalSales,
        ]);
    }
}

