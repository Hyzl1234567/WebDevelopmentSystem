<?php
namespace App\Controller;

 use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(): Response
    {
        $message = "Welcome to the dashboard! (Protected Area)";

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
            'message' => $message,
            'categories' => $categories,
        ]);
    }
}