<?php
// src/Controller/editRecipePage.php
namespace App\Controller;

use App\Entity\Recipe;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Rezept;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class editRecipePage extends AbstractController
{
    
        private $entityManager;
        public function __construct(EntityManagerInterface $entityManager)
        {
            $this->entityManager = $entityManager;
        }
    
        #[Route('/rezept/{id}/bearbeiten')]
        public function editRecipe($id): Response
        {
            $recipe = $this->entityManager->getRepository(Recipe::class)
                ->findRecipeById($id);
            
            if (!$recipe) {
                throw $this->createNotFoundException(
                    'No rezept found for id '.$id
                );
            }
            
            return $this->render('editRecipePage.html.twig', [
                'recipe' => $recipe
            ]);
        }
    
        #[Route('/rezept/{id}/bearbeiten', methods: ['POST'])]
        public function updateRecipe(Request $request, $id): Response
        {
            $data = json_decode($request->getContent(), true);

            $recipe = $this->entityManager->getRepository(Recipe::class)->findRecipeById($id);
            if (!$recipe) {
                throw $this->createNotFoundException('No recipe found for id '.$id);
            }

            $recipe->setName($data['name']);
            $recipe->setDescription($data['description']);
            $recipe->setImage($data['image']);
            $recipe->setInstructions($data['instructions']);
            
            $this->entityManager->flush();

            return new JsonResponse(['message' => 'Recipe updated successfully', 'id' => $id]);
        }

        /**
     * @Route("/ajax/save", name="ajax_save")
     */
    public function saveAction(Request $request): Response
    {
        // This example assumes you're sending data as JSON
        $data = json_decode($request->getContent(), true);

        // Process your data here...

        return new Response(json_encode(['status' => 'success']), 200, ['Content-Type' => 'application/json']);
    }
}