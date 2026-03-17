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
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Service\UploadService;

class EditRecipeController extends AbstractController
{
    
    private $entityManager;
    private UploadService $uploadService;

    public function __construct(EntityManagerInterface $entityManager, UploadService $uploadService)
    {
        $this->entityManager = $entityManager;
        $this->uploadService = $uploadService;
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
        $data = $this->getRecipeDataFromRequest($request);

        if (!$this->isDataComplete($data)) 
            return new JsonResponse(['error' => 'Missing required fields'], 400);
        if (!is_numeric($id)) 
            return new JsonResponse(['error' => 'Invalid recipe ID'], 400);

        $recipe = $this->entityManager->getRepository(Recipe::class)->find($id);
        
        if (!$recipe)
            return new JsonResponse(['error' => 'Recipe not found'], 404);
        
        $user = $this->getUser();
        $recipe->setUser($user);

        $imageErrorResponse = $this->handleImageUpload($request, $recipe);
        if ($imageErrorResponse !== null) {
            return $imageErrorResponse;
        }

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
            if (!isset($ingredientData['name'])) 
                return false;
            
            if (($ingredientData['amount'] !== null && $ingredientData['amount'] !== '') && !is_numeric($ingredientData['amount'])) 
                return false;
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
            if (!isset($ingredientData['unit']) || $ingredientData['unit'] == '') 
                $ingredient->setUnit(null);
            else
                $ingredient->setUnit($ingredientData['unit']);
            if (!isset($ingredientData['amount']) || $ingredientData['amount'] == '') 
                $ingredient->setAmount(null);
            else
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
    public function ingredientTemplate(Request $request): Response
    {
        if (!$request->isXmlHttpRequest()) {
            throw new \Exception('This is not an AJAX request');
        }
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
        $data = $this->getRecipeDataFromRequest($request);

        if (!$this->isDataComplete($data)) 
            return new JsonResponse(['error' => 'Missing required fields'], 400);

        $recipe = new Recipe();

        $imageErrorResponse = $this->handleImageUpload($request, $recipe);
        if ($imageErrorResponse !== null) {
            return $imageErrorResponse;
        }

        if (!$recipe->getImage()) {
            $recipe->setImage('https://cdn.midjourney.com/3991d0bf-e010-41a8-a6e0-2e37375c2914/0_1.png');
        }

        $this->setCoreAttributes($recipe, $data);
        $this->setIngredients($recipe, $data, 0);

        $user = $this->getUser();
        $recipe->setUser($user);

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

    /**
     * Retrieves recipe data from the request, supporting both form-encoded and raw JSON payloads.
     * 
     * @param Request $request
     * @return array
     */
    private function getRecipeDataFromRequest(Request $request): array
    {
        $rawJson = $request->request->get('recipeData');
        if (is_string($rawJson) && $rawJson !== '') {
            $decoded = json_decode($rawJson, true);
            return is_array($decoded) ? $decoded : []; # Fallback to empty array if JSON is invalid
        }

        $decoded = json_decode($request->getContent(), true);
        return is_array($decoded) ? $decoded : []; # Fallback to empty array if JSON is invalid
    }

    /**
     * Handles image upload for a recipe, including validation and storage.
     * 
     * @param Request $request
     * @param Recipe $recipe
     * @return JsonResponse|null Returns a JsonResponse on error, or null on success.
     */
    private function handleImageUpload(Request $request, Recipe $recipe): ?JsonResponse
    {
        $imageFile = $request->files->get('image');
        if (!$imageFile instanceof UploadedFile) {
            return null;
        }

        if (!$imageFile->isValid()) {
            return new JsonResponse(['error' => 'Image upload failed'], 400);
        }

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($imageFile->getMimeType(), $allowedMimeTypes, true)) {
            return new JsonResponse(['error' => 'Invalid image type. Allowed: JPG, PNG, WEBP, GIF'], 400);
        }

        if ($imageFile->getSize() > 5 * 1024 * 1024) {
            return new JsonResponse(['error' => 'Image is too large (max 5MB)'], 400);
        }

        $previousImage = $recipe->getImage();
        $storedFileName = $this->uploadService->upload($imageFile);
        $recipe->setImage('/uploads/' . $storedFileName);

        if (is_string($previousImage) && str_starts_with($previousImage, '/uploads/')) {
            $oldFilePath = $this->getParameter('kernel.project_dir') . '/public' . $previousImage;
            if (is_file($oldFilePath)) {
                @unlink($oldFilePath); #delete old image file
            }
        }

        return null;
    }
}