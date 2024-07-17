<?php

// src/Service/OpenAIService.php
namespace App\Service;

use OpenAI;

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
}
