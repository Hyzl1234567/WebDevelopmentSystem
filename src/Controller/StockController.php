<?php

namespace App\Controller;

 
use App\Entity\Stock;
use App\Form\StockType;
use App\Repository\StockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/stock')]
class StockController extends AbstractController
{
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
        $form = $this->createForm(StockType::class, $stock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
        if ($this->isCsrfTokenValid('delete' . $stock->getId(), $request->request->get('_token'))) {

            $product = $stock->getProduct();

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
        }

        return $this->redirectToRoute('app_stock_index');
    }
}
