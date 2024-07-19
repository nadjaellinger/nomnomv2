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
        $this->denyAccessUnlessGranted('ROLE_USER');
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
        $this->denyAccessUnlessGranted('ROLE_USER');
        $data = json_decode($request->getContent(), true);

        if (!$this->isDataComplete($data)) 
            return new JsonResponse(['error' => 'Missing required fields'], 400);
        if (!is_numeric($id)) 
            return new JsonResponse(['error' => 'Invalid recipe ID'], 400);

        $recipe = $this->entityManager->getRepository(Recipe::class)->find($id);
        
        if (!$recipe)
            return new JsonResponse(['error' => 'Recipe not found'], 404);
        

        $this->setCoreAttributes($recipe, $data);
        $this->setIngredients($recipe, $data, $id);

        try {
            $this->entityManager->persist($recipe);
            $this->entityManager->flush();
        } catch (ORMException $e) {
            return new JsonResponse(['error' => 'Failed to update recipe: ' . $e->getMessage()], 400);
        }
        return new JsonResponse(['message' => 'Recipe updated successfully', 'redirect' => '/rezept/'.$id]);
    }

    public function isDataComplete($data): bool
    {
        if (!isset($data['name']) || !isset($data['description']) || !isset($data['instructions']) || !isset($data['ingredients'])) {
            return false;
        }

        foreach($data['ingredients'] as $ingredientData) {
            if (!isset($ingredientData['name']) || !isset($ingredientData['unit']) || !isset($ingredientData['amount'])) {
                return false;
            }
            elseif (!is_numeric($ingredientData['amount'])) {
                return false;
            }
        }
        return true;
    }

    public function setCoreAttributes($recipe, $data): void
    {
        $recipe->setName($data['name'] ?? $recipe->getName());
            $recipe->setDescription($data['description'] ?? $recipe->getDescription());
            $recipe->setInstructions($data['instructions'] ?? $recipe->getInstructions());
    }

    public function setIngredients($recipe, $data, $id): void
    {
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
            $ingredient->setUnit($ingredientData['unit']);
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
    }

    #[Route('/ingredient/template', name: 'ingredient_template')]
    public function ingredientTemplate(): Response
    {
        return $this->render('ingredient/template.html.twig');
    }

    #[Route('/rezept/neu', methods: ['GET'])]
    public function newRecipe(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        return $this->render('newRecipePage.html.twig');
    }

    #[Route('/rezept/neu', methods: ['POST'])]
    public function createRecipe(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $data = json_decode($request->getContent(), true);

        if (!$this->isDataComplete($data)) 
            return new JsonResponse(['error' => 'Missing required fields'], 400);

        $recipe = new Recipe();
        if (!isset($data['image']))
            $recipe->setImage('default.jpg');
        $this->setCoreAttributes($recipe, $data);
        $this->setIngredients($recipe, $data, 0);

        try {
            $this->entityManager->persist($recipe);
            $this->entityManager->flush();
        } catch (ORMException $e) {
            return new JsonResponse(['error' => 'Failed to create recipe: ' . $e->getMessage()], 400);
        }
        $recipeId = strval($recipe->getId()); 
        if (!$recipeId) {
            return new JsonResponse(['error' => 'Failed to retrieve recipe ID after creation'], 500);
        }
        return new JsonResponse(['message' => 'Recipe created successfully', 'redirect' => '/rezept/'.$recipeId]);
    }
}