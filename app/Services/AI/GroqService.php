<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use League\CommonMark\CommonMarkConverter;

class GroqService implements AiServiceInterface
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.groq.com/openai/v1';
    protected string $defaultModel = 'llama-3.3-70b-versatile';

    public function __construct()
    {
        $this->apiKey = config('services.groq.api_key');
    }

    public function chat(array $messages, array $options = []): array
    {
        $model = $options['model'] ?? $this->defaultModel;
        $temperature = $options['temperature'] ?? 0.7;
        $maxTokens = $options['max_tokens'] ?? 2000;
        $images = $options['images'] ?? [];

        // Add images to messages if Llama 4 model
        if (!empty($images) && str_contains($model, 'llama-4')) {
            $messages = $this->addImagesToMessages($messages, $images);
        }

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

                Log::error('Groq API Error', [
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
            Log::error('Groq Service Error', ['error' => $e->getMessage()]);
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
            Log::error('Groq Stream Error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function generateImage(string $prompt, array $options = []): string
    {
        throw new \Exception('Image generation not supported by Groq. Use Pollinations service.');
    }

    public function editImage(string $imagePath, string $prompt, array $options = []): string
    {
        throw new \Exception('Image editing not supported by Groq. Use Pollinations service.');
    }

    public function getAvailableModels(): array
    {
        return [
            // Featured Systems
            'allam-2-7b' => 'Allam 2 7B (7000 rpd)',
'canopylabs/orpheus-arabic-saudi' => 'Orpheus Arabic Saudi (100 rpd)',
'canopylabs/orpheus-v1-english' => 'Orpheus v1 English (100 rpd)',
'groq/compound' => 'Groq Compound - AI System with Tools (250 rpd)',
'groq/compound-mini' => 'Groq Compound Mini (250 rpd)',
'llama-3.1-8b-instant' => 'LLaMA 3.1 8B Instant (14400 rpd)',
'llama-3.3-70b-versatile' => 'LLaMA 3.3 70B Versatile (1000 rpd)',
'meta-llama/llama-4-maverick-17b-128e-instruct' => 'LLaMA 4 Maverick 17B 128e Instruct (img) (1000 rpd)',
'meta-llama/llama-4-scout-17b-16e-instruct' => 'LLaMA 4 Scout 17B 16e Instruct (img) (1000 rpd)',
'meta-llama/llama-guard-4-12b' => 'LLaMA Guard 4 12B (14400 rpd)',
'meta-llama/llama-prompt-guard-2-22m' => 'LLaMA Prompt Guard 2 22M (14400 rpd)',
'meta-llama/llama-prompt-guard-2-86m' => 'LLaMA Prompt Guard 2 86M (14400 rpd)',
'moonshotai/kimi-k2-instruct' => 'Kimi K2 Instruct (1000 rpd)',
'moonshotai/kimi-k2-instruct-0905' => 'Kimi K2 Instruct 0905 (1000 rpd)',
'openai/gpt-oss-120b' => 'GPT OSS 120B (1000 rpd)',
'openai/gpt-oss-20b' => 'GPT OSS 20B (1000 rpd)',
'openai/gpt-oss-safeguard-20b' => 'GPT OSS Safeguard 20B (1000 rpd)',
'qwen/qwen3-32b' => 'Qwen3 32B (1000 rpd)',
'whisper-large-v3' => 'Whisper Large v3 (2000 rpd)',
'whisper-large-v3-turbo' => 'Whisper Large v3 Turbo (2000 rpd)',

        ];
    }

    public function countTokens(string $text): int
    {
        // Rough estimation: 1 token ≈ 4 characters
        return (int) ceil(strlen($text) / 4);
    }

    protected function addImagesToMessages(array $messages, array $images): array
    {
        // Find last user message
        $lastUserIndex = null;
        foreach ($messages as $index => $message) {
            if ($message['role'] === 'user') {
                $lastUserIndex = $index;
            }
        }

        if ($lastUserIndex === null) {
            return $messages;
        }

        // Convert last user message to multimodal format
        $content = [
            ['type' => 'text', 'text' => $messages[$lastUserIndex]['content']],
        ];

        foreach ($images as $imageInfo) {
            $content[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => 'data:' . $imageInfo['mime_type'] . ';base64,' . $imageInfo['data'],
                ],
            ];
        }

        $messages[$lastUserIndex]['content'] = $content;

        return $messages;
    }
}
