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
use Doctrine\ORM\Exception\ORMException;

class editRecipePage extends AbstractController
{
    
        private $entityManager;
        public function __construct(EntityManagerInterface $entityManager)
        {
            $this->entityManager = $entityManager;
        }
    
        #[Route('/rezept/{id}/bearbeiten', methods: ['GET'])]
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

            $recipe = $this->entityManager->getRepository(Recipe::class)->find($id);
            if (!$recipe) {
                throw $this->createNotFoundException('No recipe found for id '.$id);
            }

            $recipe->setName($data['name'] ?? $recipe->getName());
            $recipe->setDescription($data['description'] ?? $recipe->getDescription());
            $recipe->setInstructions($data['instructions'] ?? $recipe->getInstructions());
            
            $existingIngredients = $recipe->getIngredients();
            $updatedIngredients = new \Doctrine\Common\Collections\ArrayCollection();

            foreach ($data['ingredients'] as $ingredientData) {
                $ingredientId = intval($ingredientData['id']);
                if ($ingredientId === 0) {
                    $ingredient = new Ingredient();
                    $ingredient->setRecipe($recipe);
                } else {
                    $ingredient = $this->entityManager->getRepository(Ingredient::class)->find($ingredientId);
                    if (!$ingredient) {
                        $ingredient = new Ingredient();
                        $ingredient->setRecipe($recipe);
                    }
                }
                $ingredient->setName($ingredientData['name']);
                $ingredient->setAmountType($ingredientData['unit']);
                $ingredient->setAmount(intval($ingredientData['amount']));
                $updatedIngredients->add($ingredient);
                $this->entityManager->persist($ingredient);
            }

            // Remove ingredients that are no longer present in the updated data
            foreach ($existingIngredients as $existingIngredient) {
                if (!$updatedIngredients->contains($existingIngredient)) {
                    $recipe->removeIngredient($existingIngredient);
                    $this->entityManager->remove($existingIngredient);
                }
            }

            try {
                $this->entityManager->persist($recipe);
                $this->entityManager->flush();
            } catch (ORMException $e) {
                return new JsonResponse(['error' => 'Failed to update recipe: ' . $e->getMessage()], 400);
            }
            return new JsonResponse(['message' => 'Recipe updated successfully', 'redirect' => '/rezept/'.$id]);
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

    #[Route('/ingredient/template', name: 'ingredient_template')]
    public function ingredientTemplate(): Response
    {
        return $this->render('ingredient/template.html.twig');
    }
}