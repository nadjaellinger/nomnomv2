<?php
// src/Controller/landingPage.php
namespace App\Controller;

use App\Entity\Recipe;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Rezept;
use Doctrine\ORM\EntityManagerInterface;

class LandingPageController extends AbstractController
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
        if (!$recipe) {
            return $this->render('newRecipePage.html.twig', [
            ]);
        }
        return $this->render('landingPage.html.twig', [
            'recipe' => $recipe
        ]);
    }

}