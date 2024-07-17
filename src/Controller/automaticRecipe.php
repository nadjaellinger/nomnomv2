<?php
// src/Controller/automaticRecipe.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Rezept;
use Doctrine\ORM\EntityManagerInterface;
use OpenAI;
use App\Service\OpenAIService;
use Throwable;

use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

class automaticRecipe extends AbstractController
{

    private $entityManager;
    private $openAIService;
    public function __construct(EntityManagerInterface $entityManager, OpenAIService $openAIService)
    {
        $this->entityManager = $entityManager;
        $this->openAIService = $openAIService;
    }

    #[Route('/automaticRecipe')]
    public function openAI(): Response
    {
        $test = "Sure, here's a miserable attempt at keeping you alive: **Grilled Chicken with Quinoa Salad** *Ingredients*: - 1 sad, skinless chicken breast - 1 cup of quinoa (a.k.a. edible pebbles) - 2 cups water - Handful of spinach (so you can feel like Popeye) - 1 tomato, diced (try not to cry) - 1/2 cucumber, chopped (sigh) - 1 tablespoon olive oil (liquid gold) - Juice of 1 lemon (for that sour life) *Instructions*: 1. Boil water, dump in quinoa, simmer for 15 min. Try not to fall asleep. 2. Grill the chicken breast until it’s not raw. Hopefully, you know what that looks like. 3. Mix spinach, tomato, cucumber, quinoa, olive oil, and lemon juice. Fake a smile. 4. Throw the grilled chicken on top and pretend it’s gourmet. Done. Bon appétit, or whatever. ";
        try {
            $output = $this->openAIService->createJSON($test);
        } catch (Throwable $e) {
            $output = 'Caught exception: ' .  $e->getMessage() . "\n";
        }

        return $this->render('automaticRecipe.html.twig', [
            'output' => $output,
        ]);
    }
}
