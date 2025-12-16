<?php

namespace App\Controller;

use App\Repository\ActivityLogRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        UserRepository $userRepository,
        ActivityLogRepository $activityLogRepository,
        EntityManagerInterface $entityManager
    ): Response {
        // Get statistics
        $totalUsers = $userRepository->count([]);
        
        // Count admins and staff
        $allUsers = $userRepository->findAll();
        $totalAdmins = 0;
        $totalStaff = 0;
        
        foreach ($allUsers as $user) {
            if (in_array('ROLE_ADMIN', $user->getRoles())) {
                $totalAdmins++;
            }
            if (in_array('ROLE_STAFF', $user->getRoles())) {
                $totalStaff++;
            }
        }
        
        // Count products
        $totalProducts = 0;
        try {
            $connection = $entityManager->getConnection();
            $result = $connection->executeQuery('SELECT COUNT(*) as count FROM product');
            $totalProducts = $result->fetchOne();
        } catch (\Exception $e) {
            $totalProducts = 0;
        }

        // Count categories
        $totalCategories = 0;
        try {
            $connection = $entityManager->getConnection();
            $result = $connection->executeQuery('SELECT COUNT(*) as count FROM category');
            $totalCategories = $result->fetchOne();
        } catch (\Exception $e) {
            $totalCategories = 0;
        }

        // Count stocks
        $totalStocks = 0;
        try {
            $connection = $entityManager->getConnection();
            $result = $connection->executeQuery('SELECT COUNT(*) as count FROM stock');
            $totalStocks = $result->fetchOne();
        } catch (\Exception $e) {
            $totalStocks = 0;
        }

        // Get recent activities
        $recentActivities = $activityLogRepository->findRecentActivities(10);

        $categories = [
            ['name' => 'Coffee', 'image' => 'coffee.png', 'description' => 'Rich espresso and brewed coffee.'],
            ['name' => 'Tea', 'image' => 'tea.png', 'description' => 'Soothing hot or iced teas.'],
            ['name' => 'Smoothie', 'image' => 'smoothie.png', 'description' => 'Fresh and fruity blends.'],
            ['name' => 'Pastry', 'image' => 'pastry.png', 'description' => 'Crispy and buttery delights.'],
            ['name' => 'Dessert', 'image' => 'dessert.png', 'description' => 'Sweet treats and indulgent bites.'],
            ['name' => 'Vegan', 'image' => 'vegan.png', 'description' => 'Plant-based goodness.'],
            ['name' => 'Seasonal', 'image' => 'seasonal.png', 'description' => 'Limited-time seasonal favorites.'],
            ['name' => 'Combo', 'image' => 'combo.png', 'description' => 'Perfect drink and snack combos.'],
        ];

        return $this->render('dashboard/index.html.twig', [
            'categories' => $categories,
            'totalUsers' => $totalUsers,
            'totalAdmins' => $totalAdmins,
            'totalStaff' => $totalStaff,
            'totalProducts' => $totalProducts,
            'totalCategories' => $totalCategories,
            'totalStocks' => $totalStocks,
            'recentActivities' => $recentActivities,
        ]);
    }
}