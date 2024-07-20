<?php

// src/Service/OpenAIService.php
namespace App\Service;

use OpenAI;
use Symfony\Component\HttpFoundation\Response;
use Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class OpenAIService
{
    private $client;
    private $entityManager;

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

    public function createJSON(string $text_input = null, UploadedFile $image_input = null): string
    {
        if ($text_input) 
            $JSON = $this->createJsonFromText($text_input);
        elseif ($image_input)
            $JSON = $this->createJsonFromImage($image_input);
        else
            throw new Exception('No input provided');

        return $JSON;
    }

    private function createJsonFromText(string $text_input): string
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
                    'content' => $text_input
                ],
            ],
        ]);
        return $result['choices'][0]['message']['content'];
    }

    private function createJsonFromImage(UploadedFile $image_input) 
    {
        $stringFile = fopen($image_input->getPathname(), 'r');
        $uploadedFile = $this->client->files()->upload([
            'purpose' => 'assistants',
            'file' => $stringFile
        ]);

        $image_assistant = $this->getImageAssistant();
        $assistant_id = $image_assistant->id;

        $thread = $this->client->threads()->create([
        ]);

        $this->client->threads()->messages()->create(
            $thread->id,
            [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'image_file',
                        'image_file' => [
                            'file_id' => $uploadedFile->id,
                        ],
                    ]
                ]
            ]
        );
        $run = $this->client->threads()->runs()->create(
            $thread->id,
            [
                'assistant_id' => $assistant_id,
            ]
        );
            

        return $run['choices'][0]['message']['content'];
    }

    private function getImageAssistant()
    {
        $assistants = $this->client->assistants()->list()->data;
        $image_assistant = null;
        foreach ($assistants as $assistant) {
            if ($assistant['name'] === 'image_assistant') {
                $image_assistant = $assistant;
                return $image_assistant;
            }
        }
        if (!$image_assistant) {
        $assistant = $this->client->assistants()->create([
            'instructions' => $this->getImageInstructions(),
            'name' => 'image_assistant',
            'model' => 'gpt-4o',
            'response_format' => [
                'type' => 'json_object',
            ],
        ]
        );
        return $assistant;
        }
    }

    private function getInstructions(): string
    {
        return 'You will recieve the content of a website containing a cooking recipe. Please identify the name, the description, the ingredients and the instructions and create a JSON with these keys. If you can\'t find the name, please guess it. If there is no description, invent a short one. If there are no ingredients or instructions, please leave the value empty. Please have the instructions as a string. Please keep everything in German, if the recipe is in German.  For the ingredients, please use the following format: "ingredients": ["ingredient1" ["name": "ingredient2", "quantity": "quantity, "unit": "unit"], ...]. If you cant find the value, set it to 0, if you cant find the unit, set it to "". For the image, please use the URL of the image. If there is no image, set it to ""';
    }

    private function getImageInstructions(): string
    {
        return 'You will recieve an image of a cooking recipe. Please identify the name, the description, the ingredients and the instructions and create a JSON with these keys. If you can\'t find the name, please guess it. If there is no description, invent a short one. If there are no ingredients or instructions, please leave the value empty. Please have the instructions as a string. Please keep everything in German, if the recipe is in German.  For the ingredients, please use the following format: "ingredients": ["ingredient1" ["name": "ingredient2", "quantity": "quantity, "unit": "unit"], ...]. If you cant find the value, set it to 0, if you cant find the unit, set it to "". For the image, please use the URL of the image. If there is no image, set it to ""';
    }
}
