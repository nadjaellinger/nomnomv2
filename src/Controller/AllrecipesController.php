<?php

namespace App\Controller;

use App\Entity\Recipe;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;

class AllrecipesController extends AbstractController
{
    private $entityManager;
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/rezepte')]
    public function allrecipes(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $recipes = $this->entityManager->getRepository(Recipe::class)->findAllRecipes();

        return $this->render('allrecipes.html.twig', [
            'recipes' => $recipes,
        ]);
    }

}