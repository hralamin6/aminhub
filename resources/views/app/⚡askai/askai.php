<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Mary\Traits\Toast;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Services\AI\AiServiceFactory;

new
#[Title('Ask AI (Database Assistant)')]
#[Layout('layouts.app')]
class extends Component
{
    use Toast;

    // ==========================================
    // PROPERTIES & STATE
    // ==========================================
    public ?int $selectedConversationId = null;
    public string $search = '';
    public string $question = '';

    // Active configuration state
    public string $aiProvider = 'groq';
    public string $model = 'llama-3.3-70b-versatile';
    public string $systemPrompt = "You are an expert MySQL Database Administrator. Your ONLY job is to write a single valid MySQL SELECT query based on the user's question. Use only SELECT. Do not modify the database.";
    public array $activeTables = [];
    public string $responseLanguage = 'bn'; // 'bn' (Bengali) or 'en' (English)

    // UI State
    public bool $isProcessing = false;
    public array $steps = [];
    public bool $showSettingsDrawer = false;
    public bool $showSchemaDrawer = false;
    public string $editingTitle = '';

    protected $queryString = [
        'selectedConversationId' => ['except' => null],
    ];

    // ==========================================
    // LIFECYCLE HOOKS
    // ==========================================
    public function mount(): void
    {
        // Select latest conversation or create/fallback
        $latestConv = auth()->user()
            ->aiConversations()
            ->where('ai_provider', 'database')
            ->orderBy('last_message_at', 'desc')
            ->first();

        if ($latestConv) {
            $this->selectConversation($latestConv->id);
        } else {
            // Default active tables: all metadata tables
            $this->activeTables = array_keys($this->getDatabaseTablesMetadata());

            // Set dynamic default provider and model
            $providers = $this->availableProviders;
            if (!empty($providers)) {
                $this->aiProvider = isset($providers['groq']) ? 'groq' : array_key_first($providers);
                $models = $this->availableModels;
                if (!empty($models)) {
                    $this->model = isset($models['llama-3.3-70b-versatile']) ? 'llama-3.3-70b-versatile' : array_key_first($models);
                }
            }
        }
    }

    // ==========================================
    // COMPUTED PROPERTIES
    // ==========================================
    #[Computed]
    public function availableProviders(): array
    {
        return AiServiceFactory::getAvailableProviders();
    }

    #[Computed]
    public function availableModels(): array
    {
        try {
            $service = AiServiceFactory::make($this->aiProvider);
            return $service->getAvailableModels();
        } catch (\Exception $e) {
            return [];
        }
    }

    #[Computed]
    public function conversations()
    {
        $query = auth()->user()
            ->aiConversations()
            ->where('ai_provider', 'database')
            ->orderBy('last_message_at', 'desc');

        if ($this->search) {
            $query->where('title', 'like', "%{$this->search}%");
        }

        return $query->get();
    }

    #[Computed]
    public function messages()
    {
        if (!$this->selectedConversationId) {
            return collect([]);
        }

        return AiMessage::where('ai_conversation_id', $this->selectedConversationId)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    #[Computed]
    public function selectedConversation()
    {
        if (!$this->selectedConversationId) {
            return null;
        }

        return auth()->user()
            ->aiConversations()
            ->where('ai_provider', 'database')
            ->find($this->selectedConversationId);
    }

    // ==========================================
    // DATABASE SCHEMA METADATA
    // ==========================================
    public function getDatabaseTablesMetadata(): array
    {
        return Cache::remember('askai_tables_metadata', 300, function () {
            $tables = DB::select('SHOW TABLES');
            $databaseName = config('database.connections.mysql.database');
            $columnName = 'Tables_in_' . $databaseName;
            $ignoreTables = ['migrations', 'failed_jobs', 'personal_access_tokens', 'sessions', 'cache', 'jobs', 'pulse_values', 'pulse_entries', 'pulse_aggregates', 'sessions'];

            $metadata = [];
            foreach ($tables as $table) {
                $tableName = $table->$columnName;
                if (in_array($tableName, $ignoreTables)) {
                    continue;
                }

                try {
                    $rowCount = DB::table($tableName)->count();
                    $columns = DB::select("SHOW COLUMNS FROM `{$tableName}`");
                    $colsData = [];
                    foreach ($columns as $col) {
                        $colsData[] = [
                            'field' => $col->Field,
                            'type' => $col->Type,
                            'key' => $col->Key,
                            'nullable' => $col->Null === 'YES',
                        ];
                    }

                    $metadata[$tableName] = [
                        'name' => $tableName,
                        'rows' => $rowCount,
                        'columns' => $colsData,
                    ];
                } catch (\Exception $e) {
                    // Ignore missing or system tables
                }
            }
            return $metadata;
        });
    }

    private function getSimplifiedSchemaForActiveTables(): string
    {
        $metadata = $this->getDatabaseTablesMetadata();
        $schemaText = "";

        $tablesToInclude = count($this->activeTables) > 0 ? $this->activeTables : array_keys($metadata);

        foreach ($tablesToInclude as $tableName) {
            if (!isset($metadata[$tableName])) {
                continue;
            }

            $table = $metadata[$tableName];
            $schemaText .= "Table: {$tableName}\n";
            $schemaText .= "Columns:\n";

            foreach ($table['columns'] as $col) {
                $pk = ($col['key'] === 'PRI') ? ', PRIMARY KEY' : '';
                $null = $col['nullable'] ? '' : ', NOT NULL';
                $schemaText .= "- {$col['field']} ({$col['type']}{$pk}{$null})\n";
            }
            $schemaText .= "\n";
        }

        return $schemaText;
    }

    // ==========================================
    // CONVERSATION ACTIONS
    // ==========================================
    public function createNewConversation(): void
    {
        $settings = [
            'system_prompt' => $this->systemPrompt,
            'active_tables' => array_keys($this->getDatabaseTablesMetadata()),
            'response_language' => $this->responseLanguage,
        ];

        $conv = auth()->user()->aiConversations()->create([
            'title' => null,
            'ai_provider' => 'database',
            'model' => "{$this->aiProvider}:{$this->model}",
            'system_prompt' => json_encode($settings),
            'total_tokens' => 0,
            'last_message_at' => now(),
        ]);

        $this->selectedConversationId = $conv->id;
        $this->editingTitle = '';
        $this->question = '';
        $this->activeTables = $settings['active_tables'];
        $this->success('New database chat session started!');
    }

    public function selectConversation(int $id): void
    {
        $conv = auth()->user()->aiConversations()->where('ai_provider', 'database')->find($id);
        if (!$conv) {
            return;
        }

        $this->selectedConversationId = $conv->id;
        $this->editingTitle = $conv->title ?? '';
        $this->question = '';

        // Load model/provider
        $parts = explode(':', $conv->model ?? 'groq:llama-3.3-70b-versatile', 2);
        $this->aiProvider = $parts[0] ?? 'groq';
        $this->model = $parts[1] ?? 'llama-3.3-70b-versatile';

        // Load settings from system_prompt JSON
        $settings = $this->getConversationSettings($conv);
        $this->systemPrompt = $settings['system_prompt'];
        $this->activeTables = $settings['active_tables'];
        $this->responseLanguage = $settings['response_language'];
    }

    public function deleteConversation(int $id): void
    {
        $conv = auth()->user()->aiConversations()->where('ai_provider', 'database')->find($id);
        if ($conv) {
            $conv->delete();

            if ($this->selectedConversationId === $id) {
                $this->selectedConversationId = null;
                $this->editingTitle = '';
                $latest = auth()->user()
                    ->aiConversations()
                    ->where('ai_provider', 'database')
                    ->orderBy('last_message_at', 'desc')
                    ->first();
                if ($latest) {
                    $this->selectConversation($latest->id);
                }
            }
            $this->success('Chat session deleted.');
        }
    }

    public function updateTitle(): void
    {
        if (!$this->selectedConversationId || trim($this->editingTitle) === '') {
            return;
        }

        $conv = $this->selectedConversation;
        if ($conv) {
            $conv->update(['title' => trim($this->editingTitle)]);
            $this->success('Chat renamed.');
        }
    }

    // ==========================================
    // SETTINGS DRAWER ACTIONS
    // ==========================================
    public function openSettings(): void
    {
        $this->showSettingsDrawer = true;
    }

    public function saveSettings(): void
    {
        $conv = $this->selectedConversation;
        if ($conv) {
            $settings = [
                'system_prompt' => $this->systemPrompt,
                'active_tables' => $this->activeTables,
                'response_language' => $this->responseLanguage,
            ];

            $conv->update([
                'model' => "{$this->aiProvider}:{$this->model}",
                'system_prompt' => json_encode($settings),
            ]);

            $this->success('Settings saved for this chat!');
        } else {
            $this->success('Settings applied locally. Start a chat to persist them.');
        }
        $this->showSettingsDrawer = false;
    }

    public function updatedAiProvider(): void
    {
        $models = $this->availableModels;
        if (!empty($models)) {
            $this->model = array_key_first($models);
        } else {
            $this->model = '';
        }
    }

    // ==========================================
    // EXPORT & CHART UTILITIES
    // ==========================================
    public function exportCsv(int $messageId)
    {
        $message = AiMessage::find($messageId);
        if (!$message || !isset($message->metadata['rows'])) {
            $this->warning('No data found to export');
            return null;
        }

        $rows = $message->metadata['rows'];
        $columns = $message->metadata['columns'] ?? [];

        $filename = 'db_export_' . $messageId . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($columns, $rows) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, $columns);
            foreach ($rows as $row) {
                fputcsv($handle, array_values((array)$row));
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=utf-8',
        ]);
    }

    public function changeChartType(int $messageId, string $type): void
    {
        $message = AiMessage::find($messageId);
        if ($message && $message->metadata) {
            $meta = $message->metadata;
            $meta['chart_type'] = $type;
            $message->update(['metadata' => $meta]);
        }
    }

    // ==========================================
    // CORE QUERY PROCESSOR
    // ==========================================
    public function selectSuggestion(string $promptText, string $englishQuestion): void
    {
        $this->question = $promptText;
        $this->askAi($englishQuestion);
    }

    public function askAi(?string $overrideQuestion = null): void
    {
        $currentQuestion = $overrideQuestion ?: $this->question;
        if (trim($currentQuestion) === '') {
            return;
        }

        // 1. Ensure Conversation exists
        if (!$this->selectedConversationId) {
            $this->createNewConversation();
        }

        $conv = $this->selectedConversation;
        if (!$conv) {
            return;
        }

        $this->isProcessing = true;
        $this->question = ''; // Clear box

        // Set up steps
        $this->steps = [
            'schema' => ['title' => 'Analyzing schema metadata...', 'status' => 'running'],
            'sql' => ['title' => 'Generating SQL query...', 'status' => 'pending'],
            'exec' => ['title' => 'Executing SELECT query safely...', 'status' => 'pending'],
            'explain' => ['title' => 'Formulating summary report...', 'status' => 'pending'],
        ];

        try {
            // Save User message
            $userMessage = AiMessage::create([
                'ai_conversation_id' => $conv->id,
                'role' => 'user',
                'content' => $overrideQuestion ? "{$overrideQuestion} ({$currentQuestion})" : $currentQuestion,
                'tokens' => $this->countTokens($currentQuestion),
            ]);

            // STEP 1: Schema representation
            $schemaPrompt = $this->getSimplifiedSchemaForActiveTables();
            $this->steps['schema']['status'] = 'success';
            $this->steps['sql']['status'] = 'running';

            // STEP 2: Generate SQL via LLM
            $aiService = AiServiceFactory::make($this->aiProvider);

            $systemContext = <<<EOT
{$this->systemPrompt}

SCHEMA METADATA:
{$schemaPrompt}

CRITICAL INSTRUCTIONS:
1. Return ONLY the executable MySQL SELECT query.
2. DO NOT wrap the output in markdown (like ```sql ... ```). Just return the raw SQL string.
3. Only use SELECT queries. Do NOT write INSERT, UPDATE, DELETE, ALTER, DROP, or CREATE statements.
4. Join tables correctly using key relationships.
EOT;

            $chatMessages = [
                ['role' => 'system', 'content' => $systemContext]
            ];

            // Build historical context
            $pastMessages = $conv->messages()->where('id', '<', $userMessage->id)->orderBy('created_at', 'asc')->get();
            foreach ($pastMessages as $pastMsg) {
                if ($pastMsg->isUser()) {
                    $chatMessages[] = ['role' => 'user', 'content' => $pastMsg->content];
                } else if ($pastMsg->isAssistant() && isset($pastMsg->metadata['sql'])) {
                    $chatMessages[] = ['role' => 'assistant', 'content' => "Executed SQL: " . $pastMsg->metadata['sql']];
                }
            }

            // Add user question
            $chatMessages[] = ['role' => 'user', 'content' => $currentQuestion];

            $sqlResponse = $aiService->chat($chatMessages, [
                'model' => $this->model,
                'temperature' => 0.1, // Low temperature for high deterministic accuracy
            ]);

            $generatedSql = trim($sqlResponse['content'] ?? '');
            
            $cleanSql = $generatedSql;

            // Strip HTML tags (e.g. <p>SELECT ...</p>)
            $cleanSql = strip_tags($cleanSql);

            // Decode HTML entities (e.g. converting &lt; back to <)
            $cleanSql = html_entity_decode($cleanSql, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            // Strip UTF-8 BOM if present
            if (str_starts_with($cleanSql, "\xEF\xBB\xBF")) {
                $cleanSql = substr($cleanSql, 3);
            }
            // Strip UTF-16 BOM if present
            if (str_starts_with($cleanSql, "\xFE\xFF")) {
                $cleanSql = substr($cleanSql, 2);
            }
            if (str_starts_with($cleanSql, "\xFF\xFE")) {
                $cleanSql = substr($cleanSql, 2);
            }
            // Strip zero-width spaces and other unicode control/formatting/whitespace characters
            $cleanSql = preg_replace('/^[\p{Z}\p{C}]+/u', '', $cleanSql);
            $cleanSql = preg_replace('/[\p{Z}\p{C}]+$/u', '', $cleanSql);
            $cleanSql = trim($cleanSql);

            // Remove markdown code blocks if present
            $cleanSql = preg_replace('/^```sql\s*|^```\s*|```\s*$/im', '', $cleanSql);
            $cleanSql = trim($cleanSql);
            
            // Loop to remove leading block and line comments at the start of the query
            do {
                $original = $cleanSql;
                $cleanSql = preg_replace('/^\s*\/\\*.*?\\*\//s', '', $cleanSql);
                $cleanSql = preg_replace('/^\s*(?:--|#)[^\n]*/', '', $cleanSql);
                $cleanSql = trim($cleanSql);
            } while ($cleanSql !== $original);
            
            // Loop to strip nested wrapping parentheses
            while (str_starts_with($cleanSql, '(') && str_ends_with($cleanSql, ')')) {
                $cleanSql = trim(substr($cleanSql, 1, -1));
            }
            
            $cleanSql = preg_replace('/\s+/', ' ', $cleanSql);
            $cleanSql = rtrim($cleanSql, ';');
            $cleanSql = trim($cleanSql);

            $lowerSql = strtolower($cleanSql);

            // Guardrails - Extract the first word safely
            $firstWord = '';
            if (preg_match('/^[a-z]+/i', $lowerSql, $matches)) {
                $firstWord = $matches[0];
            }
            
            $allowedVerbs = ['select', 'with', 'show', 'describe', 'explain'];
            if (!in_array($firstWord, $allowedVerbs)) {
                throw new \Exception("Security Alert: Only SELECT, WITH, SHOW, DESCRIBE, or EXPLAIN queries are permitted! Generated: " . $cleanSql);
            }

            $blacklist = ['insert', 'update', 'delete', 'drop', 'alter', 'truncate', 'rename', 'replace', 'create', 'grant', 'revoke'];
            foreach ($blacklist as $keyword) {
                if (preg_match('/\b' . $keyword . '\b/i', $lowerSql)) {
                    throw new \Exception("Security Alert: Write operations or DDL keyword '{$keyword}' detected!");
                }
            }

            // Append safe LIMIT if not present
            if (!str_contains($lowerSql, 'limit')) {
                $cleanSql .= " LIMIT 100";
            }

            $this->steps['sql']['status'] = 'success';
            $this->steps['exec']['status'] = 'running';

            // STEP 3: Execute SQL
            $startTime = microtime(true);
            $rawDatabaseData = DB::select($cleanSql);
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            $this->steps['exec']['status'] = 'success';
            $this->steps['explain']['status'] = 'running';

            // Parse columns and rows
            $columns = count($rawDatabaseData) > 0 ? array_keys((array) $rawDatabaseData[0]) : [];
            $rowsData = array_map(fn($row) => (array) $row, $rawDatabaseData);

            // Autodetect chart compatibility
            $chartData = $this->autoDetectChartData($rawDatabaseData);

            // STEP 4: Summary description from LLM
            $sampleJson = json_encode(array_slice($rowsData, 0, 15), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $langName = $this->responseLanguage === 'bn' ? 'Bengali (বাংলা)' : 'English';
            $execStatus = $this->steps['exec']['status'] ?? 'success';
            $rowCount = count($rawDatabaseData);

            $explainPrompt = <<<EOT
You are a professional business intelligence reporter.
The user asked: "{$currentQuestion}"
The SQL query executed:
```sql
{$cleanSql}
```
The query completed in {$executionTime}ms and returned {$execStatus} with total rows: {$rowCount}.
Here is the raw data sample:
{$sampleJson}

Please write a premium, summary report explaining the findings in {$langName}.
Keep it concise and clear. Use bullet points where appropriate to highlight insights.
Do NOT talk about SQL column names unless relevant; address the business labels directly.
EOT;

            $explainResponse = $aiService->chat([
                ['role' => 'user', 'content' => $explainPrompt]
            ], [
                'model' => $this->model,
                'temperature' => 0.4,
            ]);

            $explanation = $explainResponse['content'] ?? 'No explanation generated.';
            $this->steps['explain']['status'] = 'success';

            // Save Assistant response
            $assistantMessage = AiMessage::create([
                'ai_conversation_id' => $conv->id,
                'role' => 'assistant',
                'content' => $explanation,
                'tokens' => ($sqlResponse['tokens'] ?? 0) + ($explainResponse['tokens'] ?? 0),
                'metadata' => [
                    'sql' => $cleanSql,
                    'execution_time' => $executionTime,
                    'total_rows' => count($rawDatabaseData),
                    'columns' => $columns,
                    'rows' => $rowsData,
                    'chart_data' => $chartData,
                    'chart_type' => $chartData ? 'bar' : null,
                    'steps' => $this->steps,
                ],
            ]);

            $conv->update(['last_message_at' => now()]);
            $conv->incrementTokens($userMessage->tokens + $assistantMessage->tokens);

            // Auto-generate Title if first message
            if (!$conv->title) {
                $conv->update(['title' => \Str::limit($overrideQuestion ?: $currentQuestion, 35)]);
                $this->editingTitle = $conv->title;
            }

            $this->dispatch('scroll-to-bottom');

        } catch (\Exception $e) {
            // Update active step as failed
            foreach ($this->steps as $key => $step) {
                if ($step['status'] === 'running') {
                    $this->steps[$key]['status'] = 'failed';
                }
            }

            // Log database error in chat
            AiMessage::create([
                'ai_conversation_id' => $conv->id,
                'role' => 'assistant',
                'content' => "❌ **Error Occurred:** " . $e->getMessage(),
                'tokens' => 0,
                'metadata' => [
                    'sql' => $cleanSql ?? '',
                    'error' => $e->getMessage(),
                    'steps' => $this->steps,
                ],
            ]);

            $this->error('Operation failed: ' . $e->getMessage());
            $this->dispatch('scroll-to-bottom');
        } finally {
            $this->isProcessing = false;
        }
    }

    // ==========================================
    // CHART GENERATORS (SVG BASED)
    // ==========================================
    public function renderSvgChart(?array $chartData, string $type): string
    {
        if (!$chartData || empty($chartData['labels']) || empty($chartData['values'])) {
            return '<div class="text-center p-4">No chartable data available</div>';
        }

        return match ($type) {
            'line' => $this->renderLineChart($chartData['labels'], $chartData['values']),
            'pie' => $this->renderDonutChart($chartData['labels'], $chartData['values']),
            default => $this->renderBarChart($chartData['labels'], $chartData['values']),
        };
    }

    public function renderBarChart(array $labels, array $values): string
    {
        $max = count($values) > 0 ? max($values) : 0;
        $niceMax = $this->getNiceMax($max);

        $width = 600;
        $height = 280;
        $paddingLeft = 55;
        $paddingRight = 20;
        $paddingTop = 30;
        $paddingBottom = 45;

        $plotWidth = $width - $paddingLeft - $paddingRight;
        $plotHeight = $height - $paddingTop - $paddingBottom;

        $count = count($values);
        $barWidth = $count > 0 ? ($plotWidth / $count) * 0.6 : 0;
        $gap = $count > 0 ? ($plotWidth / $count) * 0.4 : 0;

        $svg = "<svg viewBox=\"0 0 $width $height\" class=\"w-full h-auto text-base-content\">\n";
        $svg .= "  <defs>\n";
        $svg .= "    <linearGradient id=\"barGrad\" x1=\"0%\" y1=\"0%\" x2=\"0%\" y2=\"100%\">\n";
        $svg .= "      <stop offset=\"0%\" stop-color=\"#3b82f6\" />\n";
        $svg .= "      <stop offset=\"100%\" stop-color=\"#60a5fa\" />\n";
        $svg .= "    </linearGradient>\n";
        $svg .= "  </defs>\n";

        // Ticks & Grid
        $ticks = 4;
        for ($i = 0; $i <= $ticks; $i++) {
            $val = ($niceMax / $ticks) * $i;
            $y = $paddingTop + $plotHeight - ($val / $niceMax) * $plotHeight;
            $svg .= "  <line x1=\"$paddingLeft\" y1=\"$y\" x2=\"" . ($width - $paddingRight) . "\" y2=\"$y\" stroke=\"currentColor\" stroke-opacity=\"0.08\" stroke-dasharray=\"3\" />\n";
            $svg .= "  <text x=\"" . ($paddingLeft - 8) . "\" y=\"" . ($y + 4) . "\" text-anchor=\"end\" class=\"text-[9px] font-mono fill-current opacity-60\">" . $this->formatNumber($val) . "</text>\n";
        }

        // Bars
        for ($i = 0; $i < $count; $i++) {
            $val = $values[$i];
            $label = $labels[$i];
            $x = $paddingLeft + ($i * ($plotWidth / $count)) + ($gap / 2);
            $barHeight = ($val / $niceMax) * $plotHeight;
            $y = $paddingTop + $plotHeight - $barHeight;

            $svg .= "  <g class=\"group cursor-pointer\">\n";
            $svg .= "    <rect x=\"$x\" y=\"$y\" width=\"$barWidth\" height=\"$barHeight\" fill=\"url(#barGrad)\" rx=\"3\" ry=\"3\" class=\"transition-all duration-200 hover:fill-blue-500\">\n";
            $svg .= "      <title>{$label}: " . number_format($val) . "</title>\n";
            $svg .= "    </rect>\n";
            
            // Value tag on hover
            $svg .= "    <text x=\"" . ($x + $barWidth / 2) . "\" y=\"" . ($y - 6) . "\" text-anchor=\"middle\" class=\"text-[9px] font-bold fill-current opacity-0 group-hover:opacity-100 transition-opacity duration-150\">" . number_format($val) . "</text>\n";

            // Labels
            $labelX = $x + $barWidth / 2;
            $labelY = $paddingTop + $plotHeight + 14;
            $truncated = strlen($label) > 12 ? substr($label, 0, 10) . '..' : $label;

            if ($count > 6) {
                $svg .= "    <text x=\"$labelX\" y=\"$labelY\" text-anchor=\"end\" transform=\"rotate(-30, $labelX, $labelY)\" class=\"text-[8px] fill-current opacity-70\">" . e($truncated) . "</text>\n";
            } else {
                $svg .= "    <text x=\"$labelX\" y=\"$labelY\" text-anchor=\"middle\" class=\"text-[9px] fill-current opacity-70\">" . e($truncated) . "</text>\n";
            }
            $svg .= "  </g>\n";
        }

        $svg .= "  <line x1=\"$paddingLeft\" y1=\"" . ($paddingTop + $plotHeight) . "\" x2=\"" . ($width - $paddingRight) . "\" y2=\"" . ($paddingTop + $plotHeight) . "\" stroke=\"currentColor\" stroke-opacity=\"0.2\" />\n";
        $svg .= "</svg>";

        return $svg;
    }

    public function renderLineChart(array $labels, array $values): string
    {
        $max = count($values) > 0 ? max($values) : 0;
        $niceMax = $this->getNiceMax($max);

        $width = 600;
        $height = 280;
        $paddingLeft = 55;
        $paddingRight = 20;
        $paddingTop = 30;
        $paddingBottom = 45;

        $plotWidth = $width - $paddingLeft - $paddingRight;
        $plotHeight = $height - $paddingTop - $paddingBottom;
        $count = count($values);

        $svg = "<svg viewBox=\"0 0 $width $height\" class=\"w-full h-auto text-base-content\">\n";
        $svg .= "  <defs>\n";
        $svg .= "    <linearGradient id=\"areaGrad\" x1=\"0%\" y1=\"0%\" x2=\"0%\" y2=\"100%\">\n";
        $svg .= "      <stop offset=\"0%\" stop-color=\"#10b981\" stop-opacity=\"0.2\" />\n";
        $svg .= "      <stop offset=\"100%\" stop-color=\"#10b981\" stop-opacity=\"0.0\" />\n";
        $svg .= "    </linearGradient>\n";
        $svg .= "  </defs>\n";

        // Grid
        $ticks = 4;
        for ($i = 0; $i <= $ticks; $i++) {
            $val = ($niceMax / $ticks) * $i;
            $y = $paddingTop + $plotHeight - ($val / $niceMax) * $plotHeight;
            $svg .= "  <line x1=\"$paddingLeft\" y1=\"$y\" x2=\"" . ($width - $paddingRight) . "\" y2=\"$y\" stroke=\"currentColor\" stroke-opacity=\"0.08\" stroke-dasharray=\"3\" />\n";
            $svg .= "  <text x=\"" . ($paddingLeft - 8) . "\" y=\"" . ($y + 4) . "\" text-anchor=\"end\" class=\"text-[9px] font-mono fill-current opacity-60\">" . $this->formatNumber($val) . "</text>\n";
        }

        if ($count > 0) {
            $points = [];
            for ($i = 0; $i < $count; $i++) {
                $x = $paddingLeft + ($i * ($plotWidth / max(1, $count - 1)));
                $y = $paddingTop + $plotHeight - ($values[$i] / $niceMax) * $plotHeight;
                $points[] = "$x,$y";
            }
            $pointsStr = implode(' ', $points);

            // Shading
            $areaPoints = "$paddingLeft," . ($paddingTop + $plotHeight) . " " . $pointsStr . " " . ($paddingLeft + $plotWidth) . "," . ($paddingTop + $plotHeight);
            $svg .= "  <polygon points=\"$areaPoints\" fill=\"url(#areaGrad)\" />\n";
            
            // Line
            $svg .= "  <polyline points=\"$pointsStr\" fill=\"none\" stroke=\"#10b981\" stroke-width=\"3\" stroke-linecap=\"round\" stroke-linejoin=\"round\" />\n";

            // Markers & Labels
            for ($i = 0; $i < $count; $i++) {
                $x = $paddingLeft + ($i * ($plotWidth / max(1, $count - 1)));
                $y = $paddingTop + $plotHeight - ($values[$i] / $niceMax) * $plotHeight;
                $label = $labels[$i];
                $val = $values[$i];

                $svg .= "  <g class=\"group cursor-pointer\">\n";
                $svg .= "    <circle cx=\"$x\" cy=\"$y\" r=\"4.5\" fill=\"#ffffff\" stroke=\"#10b981\" stroke-width=\"2.5\" class=\"transition-all duration-150 hover:r-6 hover:fill-emerald-500\" />\n";
                $svg .= "    <text x=\"$x\" y=\"" . ($y - 10) . "\" text-anchor=\"middle\" class=\"text-[9px] font-bold fill-current opacity-0 group-hover:opacity-100 transition-opacity duration-150\">" . number_format($val) . "</text>\n";

                $labelY = $paddingTop + $plotHeight + 14;
                $truncated = strlen($label) > 12 ? substr($label, 0, 10) . '..' : $label;

                if ($count > 6) {
                    $svg .= "    <text x=\"$x\" y=\"$labelY\" text-anchor=\"end\" transform=\"rotate(-30, $x, $labelY)\" class=\"text-[8px] fill-current opacity-70\">" . e($truncated) . "</text>\n";
                } else {
                    $svg .= "    <text x=\"$x\" y=\"$labelY\" text-anchor=\"middle\" class=\"text-[9px] fill-current opacity-70\">" . e($truncated) . "</text>\n";
                }
                $svg .= "  </g>\n";
            }
        }

        $svg .= "  <line x1=\"$paddingLeft\" y1=\"" . ($paddingTop + $plotHeight) . "\" x2=\"" . ($width - $paddingRight) . "\" y2=\"" . ($paddingTop + $plotHeight) . "\" stroke=\"currentColor\" stroke-opacity=\"0.2\" />\n";
        $svg .= "</svg>";

        return $svg;
    }

    public function renderDonutChart(array $labels, array $values): string
    {
        $total = array_sum($values);
        if ($total <= 0) $total = 1;

        $width = 500;
        $height = 240;
        $centerX = 140;
        $centerY = 120;
        $radius = 90;
        $innerRadius = 55;

        $colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4', '#14b8a6', '#6366f1', '#6b7280'];

        $svg = "<svg viewBox=\"0 0 $width $height\" class=\"w-full h-auto text-base-content\">\n";
        
        $currentAngle = -90;
        $count = count($values);

        for ($i = 0; $i < $count; $i++) {
            $val = $values[$i];
            $label = $labels[$i];
            $color = $colors[$i % count($colors)];

            $percentage = ($val / $total) * 100;
            $angleRange = ($val / $total) * 360;

            if ($angleRange >= 360) {
                $angleRange = 359.99;
            }

            $x1 = $centerX + $radius * cos(deg2rad($currentAngle));
            $y1 = $centerY + $radius * sin(deg2rad($currentAngle));

            $endAngle = $currentAngle + $angleRange;
            $x2 = $centerX + $radius * cos(deg2rad($endAngle));
            $y2 = $centerY + $radius * sin(deg2rad($endAngle));

            $ix1 = $centerX + $innerRadius * cos(deg2rad($endAngle));
            $iy1 = $centerY + $innerRadius * sin(deg2rad($endAngle));
            $ix2 = $centerX + $innerRadius * cos(deg2rad($currentAngle));
            $iy2 = $centerY + $innerRadius * sin(deg2rad($currentAngle));

            $largeArc = $angleRange > 180 ? 1 : 0;

            $pathData = "M $x1 $y1 " .
                        "A $radius $radius 0 $largeArc 1 $x2 $y2 " .
                        "L $ix1 $iy1 " .
                        "A $innerRadius $innerRadius 0 $largeArc 0 $ix2 $iy2 " .
                        "Z";

            $svg .= "  <g class=\"group cursor-pointer\">\n";
            $svg .= "    <path d=\"$pathData\" fill=\"$color\" class=\"transition-all duration-150 hover:opacity-90\">\n";
            $svg .= "      <title>{$label}: " . number_format($val) . " (" . number_format($percentage, 1) . "%" . ")</title>\n";
            $svg .= "    </path>\n";
            $svg .= "  </g>\n";

            $currentAngle = $endAngle;
        }

        // Legend
        $legendX = 270;
        $legendYStart = 40;
        $legendGap = 18;
        $displayCount = min($count, 9);

        for ($i = 0; $i < $displayCount; $i++) {
            $label = $labels[$i];
            $val = $values[$i];
            $color = $colors[$i % count($colors)];
            $y = $legendYStart + ($i * $legendGap);

            $percentage = ($val / $total) * 100;
            $truncated = strlen($label) > 16 ? substr($label, 0, 14) . '..' : $label;

            $svg .= "  <rect x=\"$legendX\" y=\"" . ($y - 7) . "\" width=\"10\" height=\"10\" fill=\"$color\" rx=\"2\" />\n";
            $svg .= "  <text x=\"" . ($legendX + 16) . "\" y=\"" . ($y + 2) . "\" class=\"text-[10px] fill-current font-medium\">" . e($truncated) . " (" . number_format($percentage, 1) . "%)</text>\n";
        }

        if ($count > 9) {
            $otherVal = array_sum(array_slice($values, 9));
            $otherPercent = ($otherVal / $total) * 100;
            $y = $legendYStart + (9 * $legendGap);
            $svg .= "  <rect x=\"$legendX\" y=\"" . ($y - 7) . "\" width=\"10\" height=\"10\" fill=\"#6b7280\" rx=\"2\" />\n";
            $svg .= "  <text x=\"" . ($legendX + 16) . "\" y=\"" . ($y + 2) . "\" class=\"text-[10px] fill-current font-medium\">Other (" . number_format($otherPercent, 1) . "%)</text>\n";
        }

        $svg .= "</svg>";
        return $svg;
    }

    private function getNiceMax(float $max): float
    {
        if ($max <= 0) return 1;
        $log10 = log10($max);
        $pow10 = pow(10, floor($log10));
        $normalized = $max / $pow10;

        if ($normalized <= 1) return 1 * $pow10;
        if ($normalized <= 2) return 2 * $pow10;
        if ($normalized <= 5) return 5 * $pow10;
        return 10 * $pow10;
    }

    private function formatNumber(float $num): string
    {
        if ($num >= 1000000) {
            return round($num / 1000000, 1) . 'M';
        }
        if ($num >= 1000) {
            return round($num / 1000, 1) . 'K';
        }
        return (string)$num;
    }

    private function autoDetectChartData(array $rows): ?array
    {
        if (count($rows) < 1) {
            return null;
        }

        $firstRow = (array) $rows[0];
        $keys = array_keys($firstRow);

        $labelKey = null;
        $valueKey = null;

        // Try to find label key: first column that is a string/date
        foreach ($firstRow as $key => $val) {
            if (is_string($val) && !is_numeric($val)) {
                $labelKey = $key;
                break;
            }
        }

        // Try to find value key: first column that is numeric and doesn't look like ID
        foreach ($firstRow as $key => $val) {
            if (is_numeric($val)) {
                if (strtolower($key) === 'id' || str_ends_with(strtolower($key), '_id')) {
                    continue;
                }
                $valueKey = $key;
                break;
            }
        }

        // Fallbacks
        if (!$labelKey && count($keys) > 0) {
            $labelKey = $keys[0];
        }

        if (!$valueKey) {
            foreach ($firstRow as $key => $val) {
                if (is_numeric($val) && $key !== $labelKey) {
                    $valueKey = $key;
                    break;
                }
            }
            if (!$valueKey && count($keys) > 1) {
                $valueKey = $keys[1];
            }
        }

        if (!$labelKey || !$valueKey || $labelKey === $valueKey) {
            return null;
        }

        $labels = [];
        $values = [];
        // Extract up to 15 records for charting
        foreach (array_slice($rows, 0, 15) as $row) {
            $rowArr = (array) $row;
            $labels[] = strval($rowArr[$labelKey] ?? '');
            $values[] = floatval($rowArr[$valueKey] ?? 0);
        }

        return [
            'labelKey' => $labelKey,
            'valueKey' => $valueKey,
            'labels' => $labels,
            'values' => $values,
        ];
    }

    // ==========================================
    // HELPERS & SUGGESTIONS
    // ==========================================
    public function getSmartSuggestions(): array
    {
        $metadata = $this->getDatabaseTablesMetadata();
        $suggestions = [];

        if (isset($metadata['users'])) {
            $suggestions[] = [
                'text' => 'আজকে কতজন নতুন ইউজার রেজিস্টার করেছে?',
                'icon' => 'o-users',
                'question' => 'How many new users registered today?'
            ];
        }

        if (isset($metadata['products'])) {
            $suggestions[] = [
                'text' => 'সবচেয়ে কম স্টকে থাকা ৫টি প্রোডাক্ট কী কী?',
                'icon' => 'o-cube',
                'question' => 'What are the top 5 products with the lowest stock?'
            ];
        }

        if (isset($metadata['sales'])) {
            $suggestions[] = [
                'text' => 'চলতি মাসে মোট সেলস ও প্রফিট কত?',
                'icon' => 'o-banknotes',
                'question' => 'What is the total sales amount and profit for this current month?'
            ];
            $suggestions[] = [
                'text' => 'সবচেয়ে বেশি বিক্রি হওয়া সেরা ৩টি প্রোডাক্ট কী?',
                'icon' => 'o-chart-bar',
                'question' => 'What are the top 3 best-selling products by quantity?'
            ];
        } else if (isset($metadata['orders'])) {
            $suggestions[] = [
                'text' => 'সবচেয়ে বেশি অর্ডার করা ৩টি প্রোডাক্ট কী?',
                'icon' => 'o-shopping-bag',
                'question' => 'What are the top 3 most ordered products?'
            ];
        }

        if (isset($metadata['purchases'])) {
            $suggestions[] = [
                'text' => 'প্রোভাইডার ভিত্তিক মোট পারচেজ এর হিসাব দেখাও।',
                'icon' => 'o-building-office',
                'question' => 'Show total purchase amounts grouped by provider.'
            ];
        }

        if (count($suggestions) < 3) {
            $suggestions[] = [
                'text' => 'ডাটাবেসে মোট টেবিলগুলোর তালিকা ও ডাটা রো কাউন্ট দেখাও।',
                'icon' => 'o-circle-stack',
                'question' => 'List all tables in the database and their row counts.'
            ];
        }

        return array_slice($suggestions, 0, 4);
    }

    private function getConversationSettings(?AiConversation $conv): array
    {
        $defaultSettings = [
            'system_prompt' => "You are an expert MySQL Database Administrator. Your ONLY job is to write a single valid MySQL SELECT query based on the user's question. Use only SELECT. Do not modify the database.",
            'active_tables' => array_keys($this->getDatabaseTablesMetadata()),
            'response_language' => 'bn',
        ];

        if (!$conv || !$conv->system_prompt) {
            return $defaultSettings;
        }

        $decoded = json_decode($conv->system_prompt, true);
        if (is_array($decoded)) {
            return array_merge($defaultSettings, $decoded);
        }

        return array_merge($defaultSettings, ['system_prompt' => $conv->system_prompt]);
    }

    private function countTokens(string $text): int
    {
        return (int) ceil(strlen($text) / 4);
    }
}
?>
