<?php

namespace App\Tests;

use App\Controller\ImportRecipeController;
use App\Entity\Recipe;
use App\Service\OpenAIService;
use App\Service\UploadService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;

class RecipeImportSchemaTest extends TestCase
{
    public function testSchemaIngredientFieldsCanBeSaved(): void
    {
        $schema = Recipe::getJsonSchema();
        $ingredient = ['name' => 'Mehl', 'amount' => 250, 'unit' => 'g'];
        self::assertSame(array_keys($ingredient), $schema['properties']['ingredients']['items']['required']);

        $controller = new class(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(OpenAIService::class),
            $this->createMock(UploadService::class),
        ) extends ImportRecipeController {
            public function getUser(): ?UserInterface
            {
                return null;
            }
        };
        $method = new \ReflectionMethod(ImportRecipeController::class, 'createRecipe');
        $recipe = $method->invoke($controller, json_encode([
            'name' => 'Brot', 'description' => '', 'instructions' => 'Backen.',
            'image' => '', 'ingredients' => [$ingredient],
        ], JSON_THROW_ON_ERROR));

        self::assertSame('Brot', $recipe->getName());
        self::assertCount(1, $recipe->getIngredients());
        self::assertSame(250, $recipe->getIngredients()->first()->getAmount());
        self::assertSame('g', $recipe->getIngredients()->first()->getUnit());
    }
}
