<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use League\CommonMark\CommonMarkConverter;

class PollinationsService implements AiServiceInterface
{
    protected string $apiKey;
    protected string $baseUrl = 'https://gen.pollinations.ai';
    protected string $defaultModel = 'nova-micro';

    public function __construct()
    {
        $this->apiKey = config('services.pollinations.api_key');
    }

    public function chat(array $messages, array $options = []): array
    {
        $model = $options['model'] ?? $this->defaultModel;
        $temperature = $options['temperature'] ?? 0.7;
        $maxTokens = $options['max_tokens'] ?? 2000;
        $images = $options['images'] ?? [];

        // Add images to messages if provided
        if (!empty($images)) {
            $messages = $this->addImagesToMessages($messages, $images);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout(120)
            ->connectTimeout(30)
            ->post($this->baseUrl . '/v1/chat/completions', [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
                'max_tokens' => $maxTokens,
            ]);

            if ($response->failed()) {
                $errorBody = $response->json();
                $errorMessage = $errorBody['error']['message'] ?? $errorBody['message'] ?? $response->body();

                Log::error('Pollinations API Error', [
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
            Log::error('Pollinations Service Error', ['error' => $e->getMessage()]);
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
            ])->timeout(120)->post($this->baseUrl . '/v1/chat/completions', [
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
            Log::error('Pollinations Stream Error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function generateImage(string $prompt, array $options = []): string
    {
        try {
            $width = $options['width'] ?? 1024;
            $height = $options['height'] ?? 1024;
            $model = $options['model'] ?? 'flux';
            $seed = $options['seed'] ?? rand(1, 1000000);

            // Build image URL with new API
            $imageUrl = $this->baseUrl . '/image/' . urlencode($prompt);
            $imageUrl .= "?model={$model}&width={$width}&height={$height}&seed={$seed}";

            // Download the image with API key
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])
            ->timeout(120)
            ->connectTimeout(30)
            ->get($imageUrl);

            if ($response->failed()) {
                throw new \Exception('Failed to generate image. Please try again.');
            }

            // Save to temporary file
            $tempPath = storage_path('app/temp/pollinations_' . uniqid() . '.png');
            if (!is_dir(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }
            file_put_contents($tempPath, $response->body());

            return $tempPath;
        } catch (\Exception $e) {
            Log::error('Pollinations Image Generation Error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function editImage(string $imagePath, string $prompt, array $options = []): string
    {
        try {
            if (!file_exists($imagePath)) {
                throw new \Exception("Image file not found: {$imagePath}");
            }

            $model = $options['model'] ?? 'flux';
            $width = $options['width'] ?? 1024;
            $height = $options['height'] ?? 1024;

            $request = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])
            ->timeout(120)
            ->asMultipart();

            // Add the image file
            $request->attach('image', file_get_contents($imagePath), basename($imagePath));
            
            // Add prompt and other fields
            $response = $request->post($this->baseUrl . '/v1/images/edits', [
                'prompt' => $prompt,
                'model' => $model,
                'n' => 1,
                'size' => "{$width}x{$height}",
            ]);

            if ($response->failed()) {
                $error = $response->json('error.message') ?? $response->body();
                throw new \Exception("Pollinations Edit Error: {$error}");
            }

            $data = $response->json();
            $imageUrl = $data['data'][0]['url'] ?? null;

            if (!$imageUrl) {
                throw new \Exception("No image URL returned from Pollinations edit.");
            }

            // Download the result
            $imgResponse = Http::get($imageUrl);
            if ($imgResponse->failed()) {
                throw new \Exception("Failed to download edited image from {$imageUrl}");
            }

            $tempPath = storage_path('app/temp/pollinations_edit_' . uniqid() . '.png');
            if (!is_dir(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }
            file_put_contents($tempPath, $imgResponse->body());

            return $tempPath;

        } catch (\Exception $e) {
            Log::error('Pollinations Image Editing Error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getAvailableModels(): array
    {
        return [
            // Text models with vision support (all marked with img)
            'nova-micro' => 'Amazon Nova Micro (img) (25K pollen)',
            'qwen-coder' => 'Qwen3 Coder 30B (img) (4.9K pollen)',
            'gemini-lite' => 'Gemini 2.5 Flash Lite (img) (3.6K pollen)',
            'mistral' => 'Mistral Small 3.2 24B (img) (2.8K pollen)',
            'openai-nano' => 'OpenAI GPT-5 Nano (img) (950 pollen)',
            'openai-mini' => 'OpenAI GPT-5 Mini (img) (700 pollen)',
            'grok' => 'xAI Grok 4 Fast (img) (700 pollen)',
            'deepseek' => 'DeepSeek V3.2 (img) (550 pollen)',
            'perplexity-fast' => 'Perplexity Sonar (img) (500 pollen)',
            'gemini' => 'Gemini 3 Flash (img) (300 pollen)',
            'minimax' => 'MiniMax M2.1 (img) (300 pollen)',
            'perplexity-reasoning' => 'Perplexity Sonar Reasoning (img) (200 pollen)',
            'openai-audio' => 'OpenAI GPT-4o Mini Audio (img) (150 pollen)',
            'chickytutor' => 'ChickyTutor AI Tutor (img) (150 pollen)',
            'openai' => 'OpenAI GPT-5.2 (img) (100 pollen)',
            'glm' => 'Z.ai GLM-4.7 (img) (90 pollen)',
            'kimi' => 'Moonshot Kimi K2.5 (img) (85 pollen)',
            'midijourney' => 'MIDIjourney (img) (80 pollen)',
            'claude' => 'Anthropic Claude Haiku 4.5 (img) (75 pollen)',
        ];
    }

    public function getImageModels(): array
    {
        return [
            'flux' => 'Flux Schnell (5K pollen)',
            'zimage' => 'Z-Image Turbo (5K pollen)',
            'imagen-4' => 'Imagen 4 (200 pollen)',
            'flux-2-dev' => 'FLUX.2 Dev (200 pollen)',
            'klein' => 'FLUX.2 Klein 4B (150 pollen)',
            'klein-large' => 'FLUX.2 Klein 9B (85 pollen)',
            'gptimage' => 'GPT Image 1 Mini',
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
