<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use League\CommonMark\CommonMarkConverter;

class MistralService implements AiServiceInterface
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.mistral.ai/v1';
    protected string $defaultModel = 'mistral-large-2411';

    public function __construct()
    {
        $this->apiKey = config('services.mistral.api_key');
    }

    public function chat(array $messages, array $options = []): array
    {
        $model = $options['model'] ?? $this->defaultModel;
        $temperature = $options['temperature'] ?? 0.7;
        $maxTokens = $options['max_tokens'] ?? 2000;
        $images = $options['images'] ?? [];

        // Add images to messages if Pixtral model
        if (!empty($images) && str_contains($model, 'pixtral')) {
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
                'max_tokens' => $maxTokens,
            ]);

            if ($response->failed()) {
                $errorBody = $response->json();
                $errorMessage = $errorBody['error']['message'] ?? $errorBody['message'] ?? $response->body();

                Log::error('Mistral API Error', [
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
            Log::error('Mistral Service Error', ['error' => $e->getMessage()]);
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
                'max_tokens' => $maxTokens,
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
            Log::error('Mistral Stream Error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function generateImage(string $prompt, array $options = []): string
    {
        throw new \Exception('Image generation not supported by Mistral. Use Pollinations service.');
    }

    public function editImage(string $imagePath, string $prompt, array $options = []): string
    {
        throw new \Exception('Image editing not supported by Mistral. Use Pollinations service.');
    }

    public function getAvailableModels(): array
    {
        return [
        'codestral-2405' => 'Codestral 2405 (500000 tpm)',
'codestral-2501' => 'Codestral 2501 (500000 tpm)',
'codestral-mamba-2407' => 'Codestral Mamba 2407 (500000 tpm)',
'ministral-3b-2410' => 'Ministral 3B 2410 (500000 tpm)',
'ministral-8b-2410' => 'Ministral 8B 2410 (500000 tpm)',
'mistral-embed' => 'Mistral Embed (20000000 tpm)',
'mistral-large-2402' => 'Mistral Large 2402 (500000 tpm)',
'mistral-large-2407' => 'Mistral Large 2407 (500000 tpm)',
'mistral-large-2411' => 'Mistral Large 2411 (500000 tpm)',
'mistral-medium' => 'Mistral Medium (500000 tpm)',
'mistral-moderation-2411' => 'Mistral Moderation 2411 (500000 tpm)',
'mistral-saba-2502' => 'Mistral Saba 2502 (500000 tpm)',
'mistral-small-2402' => 'Mistral Small 2402 (500000 tpm)',
'mistral-small-2409' => 'Mistral Small 2409 (500000 tpm)',
'mistral-small-2501' => 'Mistral Small 2501 (500000 tpm)',
'mistral-small-2503' => 'Mistral Small 2503 (500000 tpm)',
'open-mistral-7b' => 'Open Mistral 7B (500000 tpm)',
'open-mistral-nemo' => 'Open Mistral Nemo (500000 tpm)',
'open-mixtral-8x22b' => 'Open Mixtral 8x22B (500000 tpm)',
'open-mixtral-8x7b' => 'Open Mixtral 8x7B (500000 tpm)',
'pixtral-12b-2409' => 'Pixtral 12B 2409 (img) (500000 tpm)',
'pixtral-large-2411' => 'Pixtral Large 2411 (img) (500000 tpm)',

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
