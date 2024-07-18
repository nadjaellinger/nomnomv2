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
use App\Entity\Recipe;
use App\Entity\Ingredient;

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
        $body = null;
        $html = file_get_contents('https://www.chefkoch.de/rezepte/1692201277528566/Die-schnellsten-und-besten-Muffins-ueberhaupt.html');
        //$html = file_get_contents('https://google.com');
        //$html = file_get_contents('https://www.allrecipes.com/recipe/141169/easy-indian-butter-chicken/');

        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        //find the script with the content "@type": "Recipe"
        $xpath = new \DOMXPath($dom);
        $scripts = $xpath->query('//script[@type="application/ld+json"]');
        $content = '';
        foreach ($scripts as $script) {
            $content .= strtolower(preg_replace('/\s+/', '', $script->nodeValue));
        }
        
        // Array of possible recipe types
        $recipeTypes = [
            '"@type":"recipe"',
            '"@type":["recipe","newsarticle"]',
            '"@type":["recipe","howto"]',
            '"@type":["howto","recipe"]',
            '"@type":"howto"',
        ];

        // Check if the content contains any of the recipe types
        foreach ($recipeTypes as $type) {
            if (strpos($content, $type) !== false)
            {
                $body = $content;
                $body = strip_tags($body);
            }
        }
        //strip to recipe
        if (!$body) {
            return $this->render('error.html.twig', [
                'error' => [
                    'message' => 'Es konnte kein Rezept gefunden werden',
                    'code' => 404,
                ],
            ]);
        }
        try {
            $output = $this->openAIService->createJSON($body);
            $recipe = new Recipe();
            $output = json_decode($output, true);
            $recipe->setName($output['name']) ?? '';
            $recipe->setDescription($output['description']) ?? '';
            $recipe->setInstructions($output['instructions']) ?? '';
            try
            {
                foreach ($output['ingredients'] as $ingredient) {
                    $newIngredient = new Ingredient();
                    $newIngredient->setName($ingredient['name']);
                    $newIngredient->setAmount($ingredient['quantity']);
                    $newIngredient->setUnit($ingredient['unit']);
                    $this->entityManager->persist($newIngredient);
                    $recipe->addIngredient($newIngredient);
                }
            } catch (Throwable $e) {
                $recipe->setIngredients([]);
            }
            $this->entityManager->persist($recipe);
        } catch (Throwable $e) {
            $output = 'Caught exception: ' .  $e->getMessage() . "\n";
        }

        return $this->render('editRecipePage.html.twig', [
            'recipe' => $recipe,
        ]);
    }
}
