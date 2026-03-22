<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use League\CommonMark\CommonMarkConverter;

class CerebrasService implements AiServiceInterface
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.cerebras.ai/v1';
    protected string $defaultModel = 'llama3.1-8b';

    public function __construct()
    {
        $this->apiKey = config('services.cerebras.api_key');
    }

    public function chat(array $messages, array $options = []): array
    {
        $model = $options['model'] ?? $this->defaultModel;
        $temperature = $options['temperature'] ?? 0.7;
        $maxTokens = $options['max_tokens'] ?? 2000;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout(120) // 2 minutes timeout
            ->connectTimeout(30) // 30 seconds connection timeout
            ->post($this->baseUrl . '/chat/completions', [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
                'max_completion_tokens' => $maxTokens,
            ]);

            if ($response->failed()) {
                $errorBody = $response->json();
                $errorMessage = $errorBody['error']['message'] ?? $errorBody['message'] ?? $response->body();

                Log::error('Cerebras API Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'error' => $errorMessage,
                ]);

                throw new \Exception($errorMessage);
            }

            $data = $response->json();
            $markdown = data_get($data, 'choices.0.message.content', '');
            
            if ($markdown === '') {
                throw new \RuntimeException('Empty response from AI');
            }

            $converter = new CommonMarkConverter;
            $html = $converter->convert($markdown);
            $reply = (string) $html;
            
            return [
                'content' => $reply ?? '',
                'tokens' => $data['usage']['total_tokens'] ?? 0,
                'model' => $data['model'] ?? $model,
            ];
        } catch (\Exception $e) {
            Log::error('Cerebras Service Error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function streamChat(array $messages, array $options = []): \Generator
    {
        $model = $options['model'] ?? $this->defaultModel;
        $temperature = $options['temperature'] ?? 0.7;
        $maxTokens = $options['max_tokens'] ?? 2000;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(120)->post($this->baseUrl . '/chat/completions', [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
                'max_completion_tokens' => $maxTokens,
                'stream' => true,
            ]);

            $stream = $response->toPsrResponse()->getBody();
            $buffer = '';

            while (!$stream->eof()) {
                $chunk = $stream->read(1024);
                $buffer .= $chunk;

                $lines = explode("\n", $buffer);
                $buffer = array_pop($lines);

                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line) || $line === 'data: [DONE]') {
                        continue;
                    }

                    if (str_starts_with($line, 'data: ')) {
                        $json = substr($line, 6);
                        $data = json_decode($json, true);

                        if (isset($data['choices'][0]['delta']['content'])) {
                            yield $data['choices'][0]['delta']['content'];
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Cerebras Stream Error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function generateImage(string $prompt, array $options = []): string
    {
        throw new \Exception('Image generation not supported by Cerebras. Use Pollinations service.');
    }

    public function editImage(string $imagePath, string $prompt, array $options = []): string
    {
        throw new \Exception('Image editing not supported by Cerebras. Use Pollinations service.');
    }

    public function getAvailableModels(): array
    {
        return [
            // Production Models
            'llama3.1-8b' => 'Llama 3.1 8B (Fast & Efficient)',
            'llama-3.3-70b' => 'Llama 3.3 70B (Most Capable)',
            'qwen-3-32b' => 'Qwen 3 32B (Balanced)',
            'gpt-oss-120b' => 'OpenAI GPT OSS 120B (Advanced Reasoning)',
            
            // Preview Models
            'qwen-3-235b-a22b-instruct-2507' => 'Qwen 3 235B Instruct (Preview)',
            'zai-glm-4.7' => 'Z.ai GLM 4.7 (Preview)',
        ];
    }

    public function countTokens(string $text): int
    {
        // Rough estimation: 1 token ≈ 4 characters
        return (int) ceil(strlen($text) / 4);
    }
}
