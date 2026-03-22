<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use League\CommonMark\CommonMarkConverter;

class GeminiService implements AiServiceInterface
{
    protected string $apiKey;
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';
    protected string $defaultModel = 'gemini-pro';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
    }

    public function chat(array $messages, array $options = []): array
    {
        $model = $options['model'] ?? $this->defaultModel;
        $temperature = $options['temperature'] ?? 0.7;
        $maxTokens = $options['max_tokens'] ?? 2000;
        $images = $options['images'] ?? [];

        try {
            // Convert OpenAI format to Gemini format
            $contents = $this->convertMessagesToGeminiFormat($messages, $images);

            $url = $this->baseUrl . "/models/{$model}:generateContent";

            $response = Http::timeout(120)
                ->connectTimeout(30)
                ->withQueryParameters(['key' => $this->apiKey])
                ->post($url, [
                    'contents' => $contents,
                    'generationConfig' => [
                        'temperature' => $temperature,
                        'maxOutputTokens' => $maxTokens,
                    ],
                ]);

            if ($response->failed()) {
                $errorBody = $response->json();
                $errorMessage = $errorBody['error']['message'] ?? $errorBody['message'] ?? $response->body();

                Log::error('Gemini API Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'error' => $errorMessage,
                ]);

                throw new \Exception($errorMessage, $response->status());
            }

            $data = $response->json();
          $markdown = data_get($data, 'candidates.0.content.parts.0.text', '');
          //            dd($response);
          if ($markdown === '') {
            throw new \RuntimeException('Empty response from AI');
          }

          $converter = new CommonMarkConverter;
          $html = $converter->convert($markdown);
          $reply = (string) $html;
            $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $tokens = $this->countTokens($content);

            return [
                'content' => $reply,
                'tokens' => $tokens,
                'model' => $model,
            ];
        } catch (\Exception $e) {
            Log::error('Gemini Service Error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function streamChat(array $messages, array $options = []): \Generator
    {
        $model = $options['model'] ?? $this->defaultModel;
        $temperature = $options['temperature'] ?? 0.7;
        $maxTokens = $options['max_tokens'] ?? 2000;

        try {
            $contents = $this->convertMessagesToGeminiFormat($messages);

            $url = $this->baseUrl . "/models/{$model}:streamGenerateContent";

            $response = Http::timeout(120)
                ->connectTimeout(30)
                ->withQueryParameters(['key' => $this->apiKey])
                ->post($url, [
                    'contents' => $contents,
                    'generationConfig' => [
                        'temperature' => $temperature,
                        'maxOutputTokens' => $maxTokens,
                    ],
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
                    if (empty($line)) {
                        continue;
                    }

                    $data = json_decode($line, true);
                    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                        yield $data['candidates'][0]['content']['parts'][0]['text'];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Gemini Stream Error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function generateImage(string $prompt, array $options = []): string
    {
        throw new \Exception('Image generation not supported by Gemini. Use Pollinations service.');
    }

    public function editImage(string $imagePath, string $prompt, array $options = []): string
    {
        throw new \Exception('Image editing not supported by Gemini. Use Pollinations service.');
    }

    public function getAvailableModels(): array
    {


return [
  // 🌟 GEMINI 2.5 SERIES — Free Tier Models
'gemini-2.5-flash' => 'Gemini 2.5 Flash (img) (20 rpd)',
'gemini-2.5-flash-lite' => 'Gemini 2.5 Flash Lite (img) (20 rpd)',
'gemini-2.5-flash-tts' => 'Gemini 2.5 Flash TTS (10 rpd)',
'gemini-3-flash' => 'Gemini 3 Flash (img) (20 rpd)',
'gemini-robotics-er-1.5-preview' => 'Gemini Robotics ER 1.5 Preview (img) (20 rpd)',
'gemma-3-1b' => 'Gemma 3 1B (14400 rpd)',
'gemma-3-2b' => 'Gemma 3 2B (14400 rpd)',
'gemma-3-4b' => 'Gemma 3 4B (14400 rpd)',
'gemma-3-12b' => 'Gemma 3 12B (14400 rpd)',
'gemma-3-27b' => 'Gemma 3 27B (14400 rpd)',
'gemini-embedding-1' => 'Gemini Embedding 1 (1000 rpd)',
'gemini-2.5-flash-native-audio-dialog' => 'Gemini 2.5 Flash Native Audio Dialog (Unlimited rpd)',

];

    }

    public function countTokens(string $text): int
    {
        // Rough estimation: 1 token ≈ 4 characters
        return (int) ceil(strlen($text) / 4);
    }

    protected function convertMessagesToGeminiFormat(array $messages, array $images = []): array
    {
        $contents = [];
        
        // Find the index of the last user message
        $lastUserMessageIndex = null;
        foreach ($messages as $index => $message) {
            if ($message['role'] === 'user') {
                $lastUserMessageIndex = $index;
            }
        }

        foreach ($messages as $index => $message) {
            $role = $message['role'] === 'assistant' ? 'model' : 'user';

            // Skip system messages
            if ($message['role'] === 'system') {
                continue;
            }

            $parts = [
                ['text' => $message['content']],
            ];

            // Add images to the last user message only
            if ($message['role'] === 'user' && !empty($images) && $index === $lastUserMessageIndex) {
                \Log::info('Adding images to Gemini message', [
                    'image_count' => count($images),
                    'message_index' => $index,
                    'last_user_index' => $lastUserMessageIndex,
                ]);
                
                foreach ($images as $imageInfo) {
                    // Images are now pre-encoded with data and mime_type
                    $parts[] = [
                        'inlineData' => [
                            'mimeType' => $imageInfo['mime_type'],
                            'data' => $imageInfo['data'],
                        ],
                    ];
                    
                    \Log::info('Image added to parts', [
                        'mime' => $imageInfo['mime_type'],
                        'data_length' => strlen($imageInfo['data']),
                    ]);
                }
            }

            $contents[] = [
                'role' => $role,
                'parts' => $parts,
            ];
        }

        return $contents;
    }
}
