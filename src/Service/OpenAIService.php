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
        $result = $this->client->chat()->create([
            'model' => 'gpt-4o',
            'response_format' => [
                'type' => 'json_object',
            ],
            'messages' => [
                [
                    'role' => 'system', 
                    'content' => 'You will recieve the content of a website containing a cooking recipe. Please identify the title, the description, the ingredients and the instructions and create a JSON with these keys. If you can\'t find one of these, please write ""'
                ],
                [
                    'role' => 'user', 
                    'content' => $prompt
                ],
            ],
        ]);

        return $result['choices'][0]['message']['content'];
    }
}
