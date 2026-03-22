<?php

namespace App\Services\AI;

interface AiServiceInterface
{
    /**
     * Send a message and get a response
     */
    public function chat(array $messages, array $options = []): array;

    /**
     * Stream a chat response
     */
    public function streamChat(array $messages, array $options = []): \Generator;

    /**
     * Generate an image
     */
    public function generateImage(string $prompt, array $options = []): string;

    /**
     * Edit an image
     */
    public function editImage(string $imagePath, string $prompt, array $options = []): string;

    /**
     * Get available models
     */
    public function getAvailableModels(): array;

    /**
     * Count tokens in text
     */
    public function countTokens(string $text): int;
}
