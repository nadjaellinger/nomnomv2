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
        //$html = file_get_contents('https://www.chefkoch.de/rezepte/1299041235031624/Rigatoni-al-forno.html');
        //$html = file_get_contents('https://google.com');
        $html = file_get_contents('https://www.recipetineats.com/asian-chilli-garlic-prawns-shrimp/');

        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        //find the script with the content "@type": "Recipe"
        $xpath = new \DOMXPath($dom);
        $scripts = $xpath->query('//script[@type="application/ld+json"]');
        foreach ($scripts as $script) {
            $content = $script->nodeValue;
            if (strpos($content, '"@type": "Recipe"') !== false) {
                $body = $content;
                $body = strip_tags($body);
                break;
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
        } catch (Throwable $e) {
            $output = 'Caught exception: ' .  $e->getMessage() . "\n";
        }

        return $this->render('automaticRecipe.html.twig', [
            'recipe' => $recipe,
        ]);
    }
}
