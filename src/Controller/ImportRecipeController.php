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
use App\Service\UploadService;
use Symfony\Component\HttpFoundation\File\UploadedFile;


class ImportRecipeController extends AbstractController
{

    private EntityManagerInterface $entityManager;
    private OpenAIService $openAIService;
    private UploadService $uploadService;
    public function __construct(EntityManagerInterface $entityManager, OpenAIService $openAIService, UploadService $uploadService)
    {
        $this->entityManager = $entityManager;
        $this->openAIService = $openAIService;
        $this->uploadService = $uploadService;
    }

    #[Route('/rezept/import')]
    public function automaticRecipe()
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        return $this->render('automaticRecipe.html.twig');
    }
    
    #[Route('/importRecipe', methods: ['POST'])]
    public function ImportRecipe(Request $request): Response
    {
        $text_input = null;
        $image_input = null;
        $strings = $request->request->all();
        if (array_key_exists('url', $strings) && $strings['url'] !== '') {
            $text_input = $this->extractSchemaFromWebsite($strings['url']);
        }
        elseif (array_key_exists('text', $strings) && $strings['text'] !== '') {
            $text_input = $this->getDataFromText($strings['text']);
        }
        $files = $request->files->all();
        if (isset($files['image']) && $files['image'] instanceof UploadedFile && $files['image']->isValid()) {
            $image_name = $this->uploadService->upload($files['image']);
            $image_input = new UploadedFile($this->uploadService->getTargetDirectory() . '/' . $image_name, $image_name);
        }

        if ($text_input || $image_input) {
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
        else 
            return new JsonResponse(['error' => 'No input provided'], 400);

        return new JsonResponse(['message' => 'Recipe created', 'redirect' => '/rezept/'.$recipe->getId() . '/bearbeiten'], 200);
    }

    private function getDataFromText(string $text): string
    {
        return $text;
    }

    private function validateUrl(string $url): void
    {
        // Only allow http and https schemes
        $parsed = parse_url($url);
        if (!$parsed || !isset($parsed['scheme']) || !in_array(strtolower($parsed['scheme']), ['http', 'https'], true)) {
            throw new Exception('Invalid URL: only http and https are allowed');
        }

        $host = $parsed['host'] ?? '';
        if ($host === '') {
            throw new Exception('Invalid URL: missing host');
        }

        // Resolve hostname to IP
        $ip = gethostbyname($host);
        if ($ip === $host && !filter_var($host, FILTER_VALIDATE_IP)) {
            throw new Exception('Invalid URL: could not resolve host');
        }

        // Block private, loopback, link-local, and reserved IP ranges
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            throw new Exception('Invalid URL: requests to internal or reserved addresses are not allowed');
        }

        // Explicitly block cloud metadata endpoints
        if ($ip === '169.254.169.254' || $ip === '100.100.100.200') {
            throw new Exception('Invalid URL: requests to metadata endpoints are not allowed');
        }
    }

    private function extractSchemaFromWebsite(string $url): string
    {
        $this->validateUrl($url);

        // get cacert
        $perm_cacert =  __DIR__ . '/../../certs/cacert-2025-12-02.pem';
        // Set stream context options to use the cacert file
        $options = [
            "ssl" => [
                "verify_peer" => true,
                "verify_peer_name" => true,
                "cafile" => $perm_cacert,
            ],
        ];

        $body = null;
        $html = file_get_contents(filename: $url, use_include_path: false, context: stream_context_create(options: $options));

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
            $imageUrl = $output['image'] ?? '';
            if ($imageUrl !== '') {
                try {
                    $this->validateUrl($imageUrl);
                    $recipe->setImage($imageUrl);
                } catch (Exception $e) {
                    // Unsafe or invalid image URL — store no image rather than a malicious URL
                    $recipe->setImage('');
                }
            } else {
                $recipe->setImage('');
            }
            $user = $this->getUser();
            $recipe->setUser($user);
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
                $output = 'Caught exception: ' .  $e->getMessage() . "\n";
                throw new Exception($output);
            }
            $this->entityManager->persist($recipe);
            $this->entityManager->flush();
            return $recipe;
        } 
        catch (Throwable $e) {
            $output = 'Caught exception: ' .  $e->getMessage() . "\n";
            throw new Exception($output);
        }
    }
}