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
        try {
            $output = $this->openAIService->createChat(
                'Create a short recipe for a healthy meal'
            );
        } catch (Throwable $e) {
            $output = 'Caught exception: ' .  $e->getMessage() . "\n";
        }

        return $this->render('automaticRecipe.html.twig', [
            'output' => $output,
        ]);
    }
}
