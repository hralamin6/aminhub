<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use League\CommonMark\CommonMarkConverter;

class OpenRouterService implements AiServiceInterface
{
    protected string $apiKey;
    protected string $baseUrl = 'https://openrouter.ai/api/v1';
    protected string $defaultModel = 'sourceful/riverflow-v2-fast';

    public function __construct()
    {
        $this->apiKey = config('services.openrouter.api_key');
    }

    public function chat(array $messages, array $options = []): array
    {
        $model = $options['model'] ?? $this->defaultModel;
        $temperature = $options['temperature'] ?? 0.7;
        $maxTokens = $options['max_tokens'] ?? 2000;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'HTTP-Referer' => config('app.url'),
                'X-Title' => config('app.name'),
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

                Log::error('OpenRouter API Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'error' => $errorMessage,
                ]);

                throw new \Exception($errorMessage);
            }

            $data = $response->json();
          $markdown = data_get($data, 'choices.0.message.content', '');
          //            dd($response);
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
            Log::error('OpenRouter Service Error', ['error' => $e->getMessage()]);
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
                'HTTP-Referer' => config('app.url'),
                'X-Title' => config('app.name'),
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
            Log::error('OpenRouter Stream Error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function generateImage(string $prompt, array $options = []): string
    {
        throw new \Exception('Image generation not supported by OpenRouter. Use Pollinations service.');
    }

    public function editImage(string $imagePath, string $prompt, array $options = []): string
    {
        throw new \Exception('Image editing not supported by OpenRouter. Use Pollinations service.');
    }

    public function getAvailableModels(): array
    {


      return [
       'openrouter/pony-alpha' => 'Pony Alpha',
'sourceful/riverflow-v2-pro' => 'Sourceful: Riverflow V2 Pro',
'sourceful/riverflow-v2-fast' => 'Sourceful: Riverflow V2 Fast',

'stepfun/step-3.5-flash:free' => 'StepFun: Step 3.5 Flash (free)',
'arcee-ai/trinity-large-preview:free' => 'Arcee AI: Trinity Large Preview (free)',
'upstage/solar-pro-3:free' => 'Upstage: Solar Pro 3 (free)',

'liquid/lfm-2.5-1.2b-thinking:free' => 'LiquidAI: LFM2.5-1.2B-Thinking (free)',
'liquid/lfm-2.5-1.2b-instruct:free' => 'LiquidAI: LFM2.5-1.2B-Instruct (free)',

'black-forest-labs/flux.2-klein-4b' => 'Black Forest Labs: FLUX.2 Klein 4B',
'bytedance-seed/seedream-4.5' => 'ByteDance Seed: Seedream 4.5',
'black-forest-labs/flux.2-max' => 'Black Forest Labs: FLUX.2 Max',

'nvidia/nemotron-3-nano-30b-a3b:free' => 'NVIDIA: Nemotron 3 Nano 30B A3B (free)',

'sourceful/riverflow-v2-max-preview' => 'Sourceful: Riverflow V2 Max Preview',
'sourceful/riverflow-v2-standard-preview' => 'Sourceful: Riverflow V2 Standard Preview',
'sourceful/riverflow-v2-fast-preview' => 'Sourceful: Riverflow V2 Fast Preview',

'arcee-ai/trinity-mini:free' => 'Arcee AI: Trinity Mini (free)',
'tngtech/r1t-chimera:free' => 'TNG: R1T Chimera (free)',

'black-forest-labs/flux.2-flex' => 'Black Forest Labs: FLUX.2 Flex',
'black-forest-labs/flux.2-pro' => 'Black Forest Labs: FLUX.2 Pro',

'nvidia/nemotron-nano-12b-2-vl:free' => 'NVIDIA: Nemotron Nano 12B 2 VL (free)',
'qwen/qwen3-next-80b-a3b-instruct:free' => 'Qwen: Qwen3 Next 80B A3B Instruct (free)',
'nvidia/nemotron-nano-9b-v2:free' => 'NVIDIA: Nemotron Nano 9B V2 (free)',

'openai/gpt-oss-120b:free' => 'OpenAI: gpt-oss-120b (free)',
'openai/gpt-oss-20b:free' => 'OpenAI: gpt-oss-20b (free)',

'z-ai/glm-4.5-air:free' => 'Z.AI: GLM 4.5 Air (free)',
'qwen/qwen3-coder-480b-a35b:free' => 'Qwen: Qwen3 Coder 480B A35B (free)',
'venice/uncensored:free' => 'Venice: Uncensored (free)',

'google/gemma-3n-2b:free' => 'Google: Gemma 3n 2B (free)',
'tngtech/deepseek-r1t2-chimera:free' => 'TNG: DeepSeek R1T2 Chimera (free)',
'deepseek/r1-0528:free' => 'DeepSeek: R1 0528 (free)',
'google/gemma-3n-4b:free' => 'Google: Gemma 3n 4B (free)',

'qwen/qwen3-4b:free' => 'Qwen: Qwen3 4B (free)',
'tngtech/deepseek-r1t-chimera:free' => 'TNG: DeepSeek R1T Chimera (free)',
'mistralai/mistral-small-3.1-24b:free' => 'Mistral: Mistral Small 3.1 24B (free)',

'google/gemma-3-4b:free' => 'Google: Gemma 3 4B (free)',

      ];

    }

    public function countTokens(string $text): int
    {
        // Rough estimation: 1 token ≈ 4 characters
        return (int) ceil(strlen($text) / 4);
    }
}
