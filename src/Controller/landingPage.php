<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use App\Entity\Recipe;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Rezept;
use Doctrine\ORM\EntityManagerInterface;

class landingPage extends AbstractController
{

    private $entityManager;
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/')]
    public function landingPage(): Response
    {
        $recipe = $this->entityManager->getRepository(Recipe::class)
            ->findRandomRecipe();
        return $this->render('landingPage.html.twig', [
            'recipe' => $recipe
        ]);
    }

    #[Route('/rezept/{id}')]
    public function recipe($id): Response
    {
        $recipe = $this->entityManager->getRepository(Recipe::class)
            ->findRecipeById($id);
        
        if (!$recipe) {
            throw $this->createNotFoundException(
                'No rezept found for id '.$id
            );
        }
        
        return $this->render('rezept.html.twig', [
            'rezept' => $recipe
        ]);
    }

}