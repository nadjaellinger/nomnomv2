<?php
// src/Controller/viewRecipe.php
namespace App\Controller;

use App\Entity\Recipe;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Rezept;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class viewRecipe extends AbstractController
{
    
        private $entityManager;
        public function __construct(EntityManagerInterface $entityManager)
        {
            $this->entityManager = $entityManager;
        }
    
        #[Route('/rezept/{id}')]
        public function viewRecipe($id): Response
        {
            $recipe = $this->entityManager->getRepository(Recipe::class)
                ->findRecipeById($id);
            
            if (!$recipe) {
                throw $this->createNotFoundException(
                    'No rezept found for id '.$id
                );
            }
            
            return $this->render('viewRecipe.html.twig', [
                'recipe' => $recipe
            ]);
        }
    
}