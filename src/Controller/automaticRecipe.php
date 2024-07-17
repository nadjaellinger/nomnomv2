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
        $test = "Zutaten für
5 	Tomate(n)
130 g 	Doppelrahmfrischkäse
10 	Basilikumblätter, frische, gehackt
1 TL 	Oregano, getrockneter
	Salz und Pfeffer
1 Rolle(n) 	Blätterteig aus dem Kühlregal
50 g 	Parmesan, frisch geriebener
Bring
Auf die Einkaufsliste setzen
Nährwerte pro Portion
kcal
1409
Eiweiß
41,92 g
Fett
107,13 g
Kohlenhydr.
62,81 g
Zubereitung

Arbeitszeit ca. 15 Minuten

Koch-/Backzeit ca. 20 Minuten

Gesamtzeit ca. 35 Minuten
Als erstes werden die Tomaten in 4 - 5 mm dicke Scheiben geschnitten. Damit die Tarte später nicht wässrig und damit matschig wird, sollten die Scheiben 15 Minuten zwischen Küchenpapier gelegt werden, um überschüssige Flüssigkeit los zu werden.

Für die würzige, mediterrane Creme wird das Basilikum mit Frischkäse, Salz und Pfeffer und dem getrockneten Oregano verrührt. Diese Creme wird anschließend auf dem ausgerollten Blätterteig verteilt. Der Blätterteig sollte dazu auf ein mit Backpapier belegtes Backblech gelegt werden. Auf die Cremeschicht kommen dann noch der geriebener Parmesan und die Tomatenscheiben.

Bei 200 °C (Ober-/Unterhitze) wird die Tarte anschließend 15 - 20 Minuten gebacken. Sie schmeckt warm genauso gut wie kalt und passt perfekt zu einem frischen Salat. ";
        try {
            $output = $this->openAIService->createJSON($test);
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
