<?php

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Services\AI\AiServiceFactory;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

new
#[Title('AI Chat')]
#[Layout('layouts.app')]
class extends Component
{
    use Toast;
    use WithFileUploads;

    // ==========================================
    // CONVERSATION STATE
    // ==========================================
    public ?int $selectedConversationId = null;
    public string $search = '';

    // ==========================================
    // MESSAGE STATE
    // ==========================================
    public string $message = '';
    public array $attachments = [];
    public ?int $editingMessageId = null;
    public bool $isStreaming = false;

    // ==========================================
    // AI SETTINGS
    // ==========================================
    public string $aiProvider='cerebras';
    public string $model='gpt-oss-120b';
    public string $systemPrompt = 'You are a helpful AI assistant. Do not create table. always try to write in bangla if not specified.';
    public float $temperature = 0.7;
    public int $maxTokens = 2000;

    // ==========================================
    // UI STATE
    // ==========================================
    public bool $showNewChatModal = false;
    public bool $showSettingsModal = false;
    public bool $showImageGeneratorModal = false;
    public string $imagePrompt = '';
    public string $imageModel = 'flux';
    public bool $generatingImage = false;

    // ==========================================
    // QUERY STRING
    // ==========================================
    protected $queryString = [
        'selectedConversationId' => ['except' => null],
        'search' => ['except' => ''],
    ];

    // ==========================================
    // VALIDATION RULES
    // ==========================================
    protected function rules(): array
    {
        return [
            'message' => 'required_without:attachments|string|max:10000',
            'attachments.*' => 'file|max:10240',
            'systemPrompt' => 'nullable|string|max:5000',
            'temperature' => 'numeric|min:0|max:2',
            'maxTokens' => 'integer|min:100|max:4000',
            'imagePrompt' => 'required|string|max:1000',
        ];
    }

    // ==========================================
    // LIFECYCLE HOOKS
    // ==========================================
    public function mount(): void
    {
        // Auto-select first conversation (query directly, don't use computed property)
        $firstConversation = auth()->user()
            ->aiConversations()
            ->orderBy('last_message_at', 'desc')
            ->first();
            
        if ($firstConversation) {
            $this->selectedConversationId = $firstConversation->id;
        }
    }

    public function updatingSearch(): void
    {
        // Reset selection when searching
    }

    // ==========================================
    // COMPUTED PROPERTIES
    // ==========================================
    #[Computed]
    public function conversations()
    {
        $query = auth()->user()
            ->aiConversations()
            ->where('ai_provider', '!=', 'database')
            ->with(['messages' => fn($q) => $q->latest()->limit(1)])
            ->orderBy('last_message_at', 'desc');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                    ->orWhereHas('messages', function ($mq) {
                        $mq->where('content', 'like', "%{$this->search}%");
                    });
            });
        }

        return $query->get();
    }

    #[Computed]
    public function aiMessages()
    {
        if (!$this->selectedConversationId) {
            return collect([]);
        }

        return AiMessage::where('ai_conversation_id', $this->selectedConversationId)
            ->with(['media'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    #[Computed]
    public function selectedConversation()
    {
        if (!$this->selectedConversationId) {
            return null;
        }

        return AiConversation::find($this->selectedConversationId);
    }

    // ==========================================
    // CONVERSATION ACTIONS
    // ==========================================
    public function createNewConversation(): void
    {
        $conversation = AiConversation::create([
            'user_id' => auth()->id(),
            'title' => null,
            'ai_provider' => $this->aiProvider,
            'model' => $this->model,
            'system_prompt' => $this->systemPrompt,
        ]);

        $this->selectedConversationId = $conversation->id;
        $this->showNewChatModal = false;
        $this->success('New conversation created!');
    }

    public function selectConversation(int $conversationId): void
    {
        $this->selectedConversationId = $conversationId;
        $this->message = '';
        $this->editingMessageId = null;

        // Load conversation settings
        $conversation = $this->selectedConversation;
        if ($conversation) {
            $this->aiProvider = $conversation->ai_provider;
            $this->model = $conversation->model ?? $this->getDefaultModel();
            $this->systemPrompt = $conversation->system_prompt ?? 'You are a helpful AI assistant.';
        }
    }

    public function deleteConversation(): void
    {
        if (!$this->selectedConversationId) {
            return;
        }

        $conversation = AiConversation::find($this->selectedConversationId);
        if ($conversation && $conversation->user_id === auth()->id()) {
            $conversation->delete();
            $this->selectedConversationId = null;
            $this->success('Conversation deleted successfully!');
        }
    }

    public function updateConversationTitle(string $title): void
    {
        if (!$this->selectedConversationId) {
            return;
        }

        $conversation = AiConversation::find($this->selectedConversationId);
        if ($conversation && $conversation->user_id === auth()->id()) {
            $conversation->update(['title' => $title]);
            $this->success('Title updated!');
        }
    }

    // ==========================================
    // MESSAGE ACTIONS
    // ==========================================
    public function sendMessage(): void
    {
        $this->validate(['message' => 'required|string|max:10000']);

        if (!$this->selectedConversationId) {
            $this->createNewConversation();
        }

        $conversation = $this->selectedConversation;
        if (!$conversation || $conversation->user_id !== auth()->id()) {
            $this->error('Invalid conversation');
            return;
        }

        try {
            // Save user message
            $userMessage = AiMessage::create([
                'ai_conversation_id' => $conversation->id,
                'role' => 'user',
                'content' => $this->message,
                'tokens' => $this->countTokens($this->message),
            ]);

            // Handle attachments and collect image data BEFORE Livewire cleanup
            $imageData = [];
            if (!empty($this->attachments)) {
                foreach ($this->attachments as $attachment) {
                    // Check if it's an image and encode it immediately
                    if (str_starts_with($attachment->getMimeType(), 'image/')) {
                        $tempPath = $attachment->getRealPath();
                        if (file_exists($tempPath)) {
                            $imageData[] = [
                                'data' => base64_encode(file_get_contents($tempPath)),
                                'mime_type' => $attachment->getMimeType(),
                            ];
                        }
                    }
                    
                    // Then save to media library
                    $userMessage->addMedia($attachment->getRealPath())
                        ->usingFileName($attachment->getClientOriginalName())
                        ->toMediaCollection('attachments');
                }
            }

            // Prepare messages for AI
            $messages = $this->prepareMessagesForAI($conversation);

            // Get AI service
            $aiService = AiServiceFactory::make($conversation->ai_provider);

            // Debug: Log image data
            if (!empty($imageData)) {
                \Log::info('Sending images to AI', [
                    'count' => count($imageData),
                    'provider' => $conversation->ai_provider,
                ]);
            }

            // Get AI response
            $response = $aiService->chat($messages, [
                'model' => $conversation->model,
                'temperature' => $this->temperature,
                'max_tokens' => $this->maxTokens,
                'images' => $imageData, // Pass encoded image data
            ]);


            // Save AI response
            AiMessage::create([
                'ai_conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $response['content'],
                'tokens' => $response['tokens'],
                'metadata' => [
                    'model' => $response['model'],
                    'temperature' => $this->temperature,
                ],
            ]);

            // Update conversation
            $conversation->update([
                'last_message_at' => now(),
            ]);
            $conversation->incrementTokens($userMessage->tokens + $response['tokens']);

            // Auto-generate title from first message
            if (!$conversation->title && $conversation->messages()->count() === 2) {
                $conversation->update([
                    'title' => \Str::limit($this->message, 50),
                ]);
            }

            // Reset form
            $this->reset(['message', 'attachments']);
            $this->dispatch('message-sent');
            $this->dispatch('scroll-to-bottom');

        } catch (\Exception $e) {
            \Log::error('AI Chat Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Create an error message in the chat
            AiMessage::create([
                'ai_conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => '❌ **Error:** ' . $e->getMessage(),
                'tokens' => 0,
                'metadata' => [
                    'error' => true,
                    'timestamp' => now()->toISOString(),
                ],
            ]);

            $this->error('AI Error: ' . $e->getMessage());
            $this->dispatch('scroll-to-bottom');
        }
    }

    public function regenerateResponse(int $messageId): void
    {
        $message = AiMessage::find($messageId);

        if (!$message || $message->conversation->user_id !== auth()->id()) {
            $this->error('Invalid message');
            return;
        }

        if (!$message->isAssistant()) {
            $this->error('Can only regenerate assistant messages');
            return;
        }

        try {
            $conversation = $message->conversation;

            // Get messages up to this point (excluding this message)
            $messages = $this->prepareMessagesForAI($conversation, $message->id);

            // Get AI service
            $aiService = AiServiceFactory::make($conversation->ai_provider);

            // Get new response
            $response = $aiService->chat($messages, [
                'model' => $conversation->model,
                'temperature' => $this->temperature,
                'max_tokens' => $this->maxTokens,
            ]);

            // Update message
            $message->update([
                'content' => $response['content'],
                'tokens' => $response['tokens'],
                'parent_id' => $message->id,
                'edited_at' => now(),
                'metadata' => [
                    'model' => $response['model'],
                    'temperature' => $this->temperature,
                    'regenerated' => true,
                ],
            ]);

            $this->success('Response regenerated!');
            $this->dispatch('message-regenerated');

        } catch (\Exception $e) {
            \Log::error('Regenerate Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Update message with error
            $message->update([
                'content' => '❌ **Error:** ' . $e->getMessage(),
                'tokens' => 0,
                'metadata' => [
                    'error' => true,
                    'timestamp' => now()->toISOString(),
                ],
            ]);

            $this->error('Failed to regenerate: ' . $e->getMessage());
        }
    }

    public function editMessage(int $messageId): void
    {
        $message = AiMessage::find($messageId);

        if ($message && $message->conversation->user_id === auth()->id() && $message->isUser()) {
            $this->editingMessageId = $messageId;
            $this->message = $message->content;
        }
    }

    public function updateMessage(): void
    {
        if (!$this->editingMessageId) {
            return;
        }

        $message = AiMessage::find($this->editingMessageId);

        if ($message && $message->conversation->user_id === auth()->id()) {
            $message->update([
                'content' => $this->message,
                'tokens' => $this->countTokens($this->message),
                'edited_at' => now(),
            ]);

            $this->cancelEdit();
            $this->success('Message updated!');
        }
    }

    public function cancelEdit(): void
    {
        $this->editingMessageId = null;
        $this->message = '';
    }

    public function deleteMessage(int $messageId): void
    {
        $message = AiMessage::find($messageId);

        if ($message && $message->conversation->user_id === auth()->id()) {
            $message->delete();
            $this->success('Message deleted!');
        }
    }

    public function removeAttachment(int $index): void
    {
        unset($this->attachments[$index]);
        $this->attachments = array_values($this->attachments);
    }

    // ==========================================
    // IMAGE GENERATION
    // ==========================================
    public function generateImage(): void
    {
        $this->validate(['imagePrompt' => 'required|string|max:1000']);

        $this->generatingImage = true;

        try {
            $aiService = AiServiceFactory::make('pollinations');
            $imagePath = $aiService->generateImage($this->imagePrompt, [
                'width' => 1024,
                'height' => 1024,
                'model' => $this->imageModel,
            ]);

            // Create a message with the generated image
            if ($this->selectedConversationId) {
                $conversation = $this->selectedConversation;

                $message = AiMessage::create([
                    'ai_conversation_id' => $conversation->id,
                    'role' => 'assistant',
                    'content' => "Generated image: {$this->imagePrompt}",
                    'tokens' => 0,
                    'metadata' => ['type' => 'image', 'prompt' => $this->imagePrompt],
                ]);

                $message->addMedia($imagePath)
                    ->toMediaCollection('attachments');

                $conversation->update(['last_message_at' => now()]);
            }

            $this->success('Image generated successfully!');
            $this->showImageGeneratorModal = false;
            $this->imagePrompt = '';
            $this->imageModel = 'flux';
            $this->dispatch('image-generated');

        } catch (\Exception $e) {
            \Log::error('Image Generation Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Create an error message in the chat
            if ($this->selectedConversationId) {
                $conversation = $this->selectedConversation;

                AiMessage::create([
                    'ai_conversation_id' => $conversation->id,
                    'role' => 'assistant',
                    'content' => '❌ **Image Generation Error:** ' . $e->getMessage(),
                    'tokens' => 0,
                    'metadata' => [
                        'error' => true,
                        'type' => 'image_generation_error',
                        'prompt' => $this->imagePrompt,
                    ],
                ]);
            }

            $this->error('Failed to generate image: ' . $e->getMessage());
        } finally {
            $this->generatingImage = false;
        }
    }

    // ==========================================
    // SETTINGS
    // ==========================================
    public function updateSettings(): void
    {
        $this->validate([
            'systemPrompt' => 'nullable|string|max:5000',
            'temperature' => 'numeric|min:0|max:2',
            'maxTokens' => 'integer|min:100|max:4000',
        ]);

        if ($this->selectedConversationId) {
            $conversation = $this->selectedConversation;
            if ($conversation && $conversation->user_id === auth()->id()) {
                $conversation->update([
                    'ai_provider' => $this->aiProvider,
                    'model' => $this->model,
                    'system_prompt' => $this->systemPrompt,
                ]);
            }
        }

        $this->showSettingsModal = false;
        $this->success('Settings updated!');
    }

    public function updatedAiProvider(): void
    {
        $this->model = $this->getDefaultModel();
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================
    protected function prepareMessagesForAI(AiConversation $conversation, ?int $excludeAfter = null): array
    {
        $messages = [];

        // Add system prompt
        if ($conversation->system_prompt) {
            $messages[] = [
                'role' => 'system',
                'content' => $conversation->system_prompt,
            ];
        }

        // Add conversation messages
        $query = $conversation->messages()->orderBy('created_at', 'asc');

        if ($excludeAfter) {
            $query->where('id', '<', $excludeAfter);
        }

        foreach ($query->get() as $msg) {
            if ($msg->role !== 'system') {
                $messages[] = [
                    'role' => $msg->role,
                    'content' => $msg->content,
                ];
            }
        }

        return $messages;
    }

    protected function countTokens(string $text): int
    {
        return (int) ceil(strlen($text) / 4);
    }

    protected function getDefaultModel(): string
    {
        return match ($this->aiProvider) {
            'openrouter' => 'sourceful/riverflow-v2-fast',
            'gemini' => 'gemini-2.5-flash',
            'pollinations' => 'nova-micro',
            'cerebras' => 'gpt-oss-120b',
            'mistral' => 'mistral-large-2411',
            'groq' => 'llama-3.3-70b-versatile',
            default => 'gpt-oss-120b',
        };
    }

    public function getAvailableModels(): array
    {
        try {
            $service = AiServiceFactory::make($this->aiProvider);
            return $service->getAvailableModels();
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getImageModels(): array
    {
        try {
            $service = AiServiceFactory::make('pollinations');
            if (method_exists($service, 'getImageModels')) {
                return $service->getImageModels();
            }
            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getAvailableProviders(): array
    {
        return AiServiceFactory::getAvailableProviders();
    }

    // ==========================================
    // EXPORT
    // ==========================================
    public function exportConversation(string $format = 'txt')
    {
        if (!$this->selectedConversationId) {
            $this->warning('No conversation selected');
            return;
        }

        $conversation = $this->selectedConversation;
        if (!$conversation || $conversation->user_id !== auth()->id()) {
            $this->error('Invalid conversation');
            return;
        }

        $content = "Conversation: " . $conversation->getDisplayTitle() . "\n";
        $content .= "Date: " . $conversation->created_at->format('Y-m-d H:i:s') . "\n";
        $content .= "Provider: " . $conversation->ai_provider . "\n";
        $content .= "Model: " . $conversation->model . "\n\n";
        $content .= str_repeat('=', 50) . "\n\n";

        foreach ($this->aiMessages as $message) {
            $role = strtoupper($message->role);
            $content .= "{$role}:\n{$message->content}\n\n";
            $content .= str_repeat('-', 50) . "\n\n";
        }

        $filename = 'conversation_' . $conversation->id . '_' . now()->format('Y-m-d') . '.txt';

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $filename, [
            'Content-Type' => 'text/plain',
        ]);
    }

};
