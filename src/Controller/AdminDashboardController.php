<?php
// src/Controller/editRecipePage.php
namespace App\Controller;

use App\Entity\Recipe;
use App\Entity\Ingredient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;


class AdminDashboardController extends AbstractController
{
    private $entityManager;
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/admin/dashboard', methods: ['GET'])]
    public function adminDashboard(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $recipes = $this->entityManager->getRepository(Recipe::class)
            ->findAll();
        
        return $this->render('adminDashboard.html.twig', [
            'recipes' => $recipes,
        ]);
    }
    
    #[Route('/admin/dashboard', methods: ['POST'])]
    public function deleteRecipe(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $data = json_decode($request->getContent(), true);

        if (!isset($data['id']) || !is_numeric($data['id'])) 
            return new JsonResponse(['error' => 'Invalid recipe ID'], 400);

        $recipe = $this->entityManager->getRepository(Recipe::class)->find($data['id']);
        
        if (!$recipe)
            return new JsonResponse(['error' => 'Recipe not found'], 404);
        
        $this->entityManager->remove($recipe);
        $this->entityManager->flush();
        
        return new JsonResponse(['message' => 'Recipe deleted'], 200);
    }
}