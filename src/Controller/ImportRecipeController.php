<?php
// src/Controller/automaticRecipe.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\OpenAIService;
use Throwable;
use App\Entity\Recipe;
use App\Entity\Ingredient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Exception;

use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

class ImportRecipeController extends AbstractController
{

    private $entityManager;
    private $openAIService;
    public function __construct(EntityManagerInterface $entityManager, OpenAIService $openAIService)
    {
        $this->entityManager = $entityManager;
        $this->openAIService = $openAIService;
    }

    #[Route('/rezept/import')]
    public function automaticRecipe()
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        return $this->render('automaticRecipe.html.twig');
    }
    
    #[Route('/importRecipe', methods: ['POST'])]
    public function AIadd(Request $request): Response
    {
        $data = $request->getContent();
        $data = json_decode($data, true);
        //we check first if there is anything in the textfile
        if ($data['text'] && $data['text'] !== '') {
            $text_input = $this->getDataFromText($data['text']);
        }
        //otherwise, we need to use the url
        elseif ($data['url'] && $data['url'] !== '') {
            $text_input = $this->extractSchemaFromWebsite($data['url']);
        }
        elseif ($data['file'] && $data['file'] !== '') {
            $image_input = $this->getDataFromImage($data['file']);
        }

        if ($text_input) {
            try{
                //we use the OpenAI API to create a json
                $json = $this->openAIService->createJSON($text_input, $image_input);
                //we create a recipe from the json 
                $recipe = $this->createRecipe($json);
                $this->entityManager->flush();
            }
            catch (Throwable $e) {
                return new JsonResponse(['error' => 'Error creating recipe', 'message' => $e->getMessage()], 400);
            }
        }
        return new JsonResponse(['message' => 'Recipe created', 'redirect' => '/rezept/'.$recipe->getId() . '/bearbeiten'], 200);
    }

    private function getDataFromText(string $text): string
    {
        return $text;
    }

    private function extractSchemaFromWebsite(string $url): string
    {
        $body = null;
        $html = file_get_contents($url);

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
            throw new Exception('No recipe found');
        }
        return $body;
    }

    private function createRecipe(string $content): Recipe
    {    
        try {
            $recipe = new Recipe();
            $output = json_decode($content, true);
            $recipe->setName($output['name']) ?? '';
            $recipe->setDescription($output['description']) ?? '';
            $recipe->setInstructions($output['instructions']) ?? '';
            $recipe->setImage($output['image']) ?? '';
            try
            {
                foreach ($output['ingredients'] as $ingredient) {
                    $newIngredient = new Ingredient();
                    $newIngredient->setName($ingredient['name']) ?? '';
                    $newIngredient->setAmount($ingredient['quantity']) ?? 0;
                    $newIngredient->setUnit($ingredient['unit']) ?? '';
                    $newIngredient->setRecipe($recipe);
                    $recipe->addIngredient($newIngredient);
                    $this->entityManager->persist($newIngredient);
                    $this->entityManager->persist($recipe);
                    $this->entityManager->flush();
                }
            } catch (Throwable $e) {
                
            }
        } 
        catch (Throwable $e) {
            $output = 'Caught exception: ' .  $e->getMessage() . "\n";
        }
        $this->entityManager->persist($recipe);
        $this->entityManager->flush();
        return $recipe;
    }

    function getDataFromImage(string $file): string
    {
        $image = file_get_contents($file);
        //upload the image to the server
        $upload_folder = 'uploads/';
        $filename = $upload_folder . basename($file);
        file_put_contents($filename, $image);
        return $filename;
    }

}