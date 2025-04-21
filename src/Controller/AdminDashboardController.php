<?php
// src/Controller/editRecipePage.php
namespace App\Controller;

use App\Entity\Recipe;
use App\Entity\Ingredient;
use App\Entity\User;
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
        $users = $this->entityManager->getRepository(User::class)
            ->findAll();    
        
        $pendingUsers = [];
        foreach ($users as $user) {
            if (!$user->isApproved()) {
                $pendingUsers[] = $user;
            }
        }
        
        return $this->render('adminDashboard.html.twig', [
            'recipes' => $recipes,
            'pendingUsers' => $pendingUsers,
        ]);
    }
    
    #[Route('/admin/dashboard', methods: ['POST'])]
    public function action(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $data = json_decode($request->getContent(), true);

        if (!isset($data['action'])) 
            return new JsonResponse(['error' => 'Invalid request'], 400);

        switch ($data['action']) {
            case 'deleteRecipe':
                return $this->deleteRecipe($request);
            case 'approveUser':
                return $this->approveUser($request);
            case 'deleteUser':
                return $this->deleteUser($request);
            default:
                return new JsonResponse(['error' => 'Invalid action'], 400);
        }
    }

    private function deleteRecipe(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $data = json_decode($request->getContent(), true);

        if (!isset($data['recipeId']) || !is_numeric($data['recipeId'])) 
            return new JsonResponse(['error' => 'Invalid recipe ID'], 400);

        $recipe = $this->entityManager->getRepository(Recipe::class)->find($data['recipeId']);
        
        if (!$recipe)
            return new JsonResponse(['error' => 'Recipe not found'], 404);
        
        $this->entityManager->remove($recipe);
        $this->entityManager->flush();
        
        return new JsonResponse(['message' => 'Recipe deleted'], 200);
    }

    private function approveUser(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $data = json_decode($request->getContent(), true);

        if (!isset($data['userId']) || !is_numeric($data['userId'])) 
            return new JsonResponse(['error' => 'Invalid user ID'], 400);

        $user = $this->entityManager->getRepository(User::class)->find($data['userId']);
        
        if (!$user)
            return new JsonResponse(['error' => 'User not found'], 404);
        
        $user->setIsApproved(true);
        $this->entityManager->flush();
        
        return new JsonResponse(['message' => 'User approved'], 200);
    }

    private function deleteUser(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $data = json_decode($request->getContent(), true);

        if (!isset($data['userId']) || !is_numeric($data['userId'])) 
            return new JsonResponse(['error' => 'Invalid user ID'], 400);

        $user = $this->entityManager->getRepository(User::class)->find($data['userId']);
        
        if (!$user)
            return new JsonResponse(['error' => 'User not found'], 404);
        
        $this->entityManager->remove($user);
        $this->entityManager->flush();
        
        return new JsonResponse(['message' => 'User deleted'], 200);
    }

}
