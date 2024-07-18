<?php

// src/Service/OpenAIService.php
namespace App\Service;

use OpenAI;

use function PHPSTORM_META\type;

class OpenAIService
{
    private $client;

    public function __construct(string $apiKey)
    {
        $this->client = OpenAI::client($apiKey);
    }

    public function createCompletion(string $prompt): string
    {
        $result = $this->client->completions()->create([
            'model' => 'gpt-3.5-turbo',
            'prompt' => $prompt,
        ]);

        return $result['choices'][0]['text'];
    }

    public function createChat(string $prompt): string
    {
        $result = $this->client->chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => 'You are annoyed by the task, but you have to do it. You have a dark sense of humor. Keep it short'],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        return $result['choices'][0]['message']['content'];
    }

    public function createJSON(string $prompt): string
    {
        $instructions = $this->getInstructions();
        $result = $this->client->chat()->create([
            'model' => 'gpt-4o',
            'response_format' => [
                'type' => 'json_object',
            ],
            'messages' => [
                [
                    'role' => 'system', 
                    'content' => $instructions
                ],
                [
                    'role' => 'user', 
                    'content' => $prompt
                ],
            ],
        ]);
        return $result['choices'][0]['message']['content'];
    }

    private function getInstructions(): string
    {
        return 'You will recieve the content of a website containing a cooking recipe. Please identify the name, the description, the ingredients and the instructions and create a JSON with these keys. If you can\'t find the name, please guess it. If there is no description, invent a short one. If there are no ingredients or instructions, please leave the value empty. Please have the instructions as a string. Please keep everything in German, if the recipe is in German.  For the ingredients, please use the following format: "ingredients": ["ingredient1" ["name": "ingredient2", "quantity": "quantity, "unit": "unit"], ...]. If you cant find the value, set it to 0, if you cant find the unit, set it to "".';
    }
}
