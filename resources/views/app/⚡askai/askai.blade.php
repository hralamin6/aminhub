<div class="h-full flex flex-col gap-4" x-data="askAiChat()">
    <!-- Top Action Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-base-100 p-4 rounded-2xl shadow-sm border border-base-200/50">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                </svg>
            </div>
            <div>
                <h1 class="text-lg font-bold text-base-content flex items-center gap-2">
                    Database AI Assistant
                    <span class="badge badge-sm badge-success font-semibold">Live BI</span>
                </h1>
                <p class="text-xs text-base-content/60">Ask questions in natural language and analyze your database in real time.</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button @click="$wire.showSchemaDrawer = true" class="btn btn-sm btn-outline gap-1">
                <x-icon name="o-circle-stack" class="w-4 h-4" />
                Database Schema
            </button>
            <button @click="$wire.showSettingsDrawer = true" class="btn btn-sm btn-primary gap-1">
                <x-icon name="o-cog-6-tooth" class="w-4 h-4" />
                BI Settings
            </button>
        </div>
    </div>

    <!-- Main Content Workspace Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 flex-1 min-h-[550px] h-[calc(100vh-12rem)]">
        
        <!-- SIDEBAR: Conversations & Metadata Explorer -->
        <div class="lg:col-span-1 bg-base-100 rounded-2xl border border-base-200/50 flex flex-col overflow-hidden h-full shadow-sm">
            <!-- Sidebar Header -->
            <div class="p-4 border-b border-base-200 bg-base-50/50">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-bold text-sm text-base-content/80 tracking-wide uppercase">SQL Sessions</h3>
                    <button wire:click="createNewConversation" class="btn btn-circle btn-xs btn-ghost text-primary hover:bg-primary/10" title="New Session">
                        <x-icon name="o-plus" class="w-4 h-4" />
                    </button>
                </div>
                <div class="relative">
                    <input type="text" wire:model.live.debounce.250ms="search" placeholder="Search sessions..." class="input input-sm input-bordered w-full pr-8 bg-base-50" />
                    <span class="absolute right-2 top-2 text-base-content/40">
                        <x-icon name="o-magnifying-glass" class="w-4 h-4" />
                    </span>
                </div>
            </div>

            <!-- Sessions List -->
            <div class="flex-1 overflow-y-auto divide-y divide-base-100 p-2 space-y-1">
                @forelse($this->conversations as $conv)
                    <div wire:key="conv-{{ $conv->id }}" class="group relative rounded-xl p-3 cursor-pointer transition-all duration-150 {{ $selectedConversationId === $conv->id ? 'bg-primary/5 border border-primary/20 text-primary' : 'hover:bg-base-50 text-base-content' }}" @click="$wire.selectConversation({{ $conv->id }})">
                        <div class="flex items-start justify-between gap-2 pr-6">
                            <div class="min-w-0 flex-1">
                                <h4 class="text-xs font-semibold truncate {{ $selectedConversationId === $conv->id ? 'text-primary' : 'text-base-content' }}">
                                    {{ $conv->getDisplayTitle() }}
                                </h4>
                                <span class="text-[10px] text-base-content/50 block mt-1">
                                    {{ $conv->last_message_at?->diffForHumans() ?? 'Just started' }}
                                </span>
                            </div>
                        </div>

                        <!-- Hover Delete Trigger -->
                        <button wire:click.stop="deleteConversation({{ $conv->id }})" onclick="return confirm('Are you sure you want to delete this session?')" class="absolute right-2 top-3 p-1 rounded-md text-base-content/40 hover:text-error hover:bg-error/10 opacity-0 group-hover:opacity-100 transition-opacity duration-150">
                            <x-icon name="o-trash" class="w-3.5 h-3.5" />
                        </button>
                    </div>
                @empty
                    <div class="p-6 text-center text-base-content/50">
                        <x-icon name="o-chat-bubble-left" class="w-8 h-8 mx-auto mb-2 opacity-40" />
                        <p class="text-xs">No active chat sessions.</p>
                        <button wire:click="createNewConversation" class="btn btn-xs btn-primary mt-2">Start One</button>
                    </div>
                @endforelse
            </div>
            
            <!-- Quick Table Stats Panel (Accordion) -->
            <div class="border-t border-base-200 bg-base-50/50 p-3" x-data="{ openTablesList: false }">
                <button @click="openTablesList = !openTablesList" class="flex items-center justify-between w-full text-xs font-bold text-base-content/80">
                    <span class="flex items-center gap-1.5">
                        <x-icon name="o-circle-stack" class="w-4 h-4 text-primary" />
                        TABLE EXPLORER ({{ count($activeTables) }} Active)
                    </span>
                    <x-icon name="o-chevron-down" class="w-3.5 h-3.5 transform transition-transform duration-200" ::class="openTablesList ? 'rotate-180' : ''" />
                </button>
                
                <div x-show="openTablesList" x-cloak class="mt-3 max-h-[160px] overflow-y-auto space-y-1 text-xs">
                    @foreach($this->getDatabaseTablesMetadata() as $tblName => $tblMeta)
                        <div class="flex items-center justify-between p-1 hover:bg-base-100 rounded">
                            <span class="truncate font-mono text-[11px] text-base-content/80">{{ $tblName }}</span>
                            <span class="badge badge-sm badge-ghost text-[10px]">{{ $tblMeta['rows'] }} rows</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- WORKSPACE: Chat Screen & Dynamic Analytics -->
        <div class="lg:col-span-3 bg-base-100 rounded-2xl border border-base-200/50 flex flex-col overflow-hidden h-full shadow-sm">
            @if($selectedConversationId)
                <!-- Chat Screen Header (Rename Session) -->
                <div class="p-4 border-b border-base-200 bg-base-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <input type="text" wire:model.blur="editingTitle" wire:change="updateTitle" class="input input-xs font-bold bg-transparent border-transparent hover:border-base-300 focus:bg-base-100 focus:border-primary text-sm w-full max-w-sm truncate text-base-content" title="Click to rename session" />
                        </div>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-[10px] badge badge-outline capitalize bg-base-100">{{ $aiProvider }} • {{ $model }}</span>
                            <span class="text-[10px] text-base-content/50">Active Tables: {{ count($activeTables) }}</span>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-1.5 self-end">
                        <button wire:click="createNewConversation" class="btn btn-xs btn-outline gap-1">
                            <x-icon name="o-plus" class="w-3.5 h-3.5" /> New
                        </button>
                        <button wire:click="deleteConversation({{ $selectedConversationId }})" onclick="return confirm('Delete this session?')" class="btn btn-xs btn-ghost text-error hover:bg-error/10">
                            <x-icon name="o-trash" class="w-3.5 h-3.5" /> Delete
                        </button>
                    </div>
                </div>

                <!-- Chat History Messages Container -->
                <div class="flex-1 overflow-y-auto p-4 space-y-6 bg-base-50/40" id="messages-container" x-ref="messagesContainer">
                    @forelse($this->messages as $msg)
                        <div wire:key="msg-{{ $msg->id }}" class="flex flex-col {{ $msg->isUser() ? 'items-end' : 'items-start' }} space-y-1">
                            
                            <!-- Sender tag -->
                            <div class="flex items-center gap-2 px-1">
                                <span class="text-[10px] font-bold text-base-content/60">
                                    {{ $msg->isUser() ? 'You' : 'Database AI' }}
                                </span>
                                <span class="text-[9px] text-base-content/40">
                                    {{ $msg->created_at->format('h:i A') }}
                                </span>
                            </div>

                            <!-- Message bubble -->
                            <div class="max-w-[90%] md:max-w-[80%] rounded-2xl px-4 py-3 shadow-sm border {{ $msg->isUser() ? 'bg-primary text-primary-content border-primary/20 rounded-tr-none' : 'bg-base-100 text-base-content border-base-200 rounded-tl-none' }}">
                                <!-- User query (Plain text/markdown) or Assistant Explanation -->
                                <div class="prose prose-sm max-w-none break-words text-current {{ $msg->isUser() ? 'prose-invert' : 'dark:prose-invert' }}">
                                    {!! Str::markdown($msg->content, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                                </div>

                                <!-- Assistant Metadata & Interactive BI components -->
                                @if($msg->isAssistant() && $msg->metadata)
                                    <!-- Safe SQL Block -->
                                    @if(isset($msg->metadata['sql']))
                                        <div class="mt-4 pt-3 border-t border-base-200" x-data="{ showSql: false }">
                                            <div class="flex items-center justify-between gap-2">
                                                <button @click="showSql = !showSql" class="btn btn-xs btn-ghost gap-1 text-[11px] font-semibold text-primary">
                                                    <x-icon name="o-command-line" class="w-3.5 h-3.5" />
                                                    <span x-text="showSql ? 'Hide Generated SQL' : 'Show Generated SQL'"></span>
                                                </button>
                                                @if(isset($msg->metadata['execution_time']))
                                                    <span class="text-[10px] font-mono text-base-content/50">
                                                        Executed in {{ $msg->metadata['execution_time'] }}ms
                                                    </span>
                                                @endif
                                            </div>
                                            
                                            <div x-show="showSql" x-cloak class="mt-2 rounded-lg bg-base-800 text-slate-100 p-3 font-mono text-xs overflow-x-auto relative group/code code-block-wrapper">
                                                <code>{{ $msg->metadata['sql'] }}</code>
                                                <button onclick="copyCode(this)" class="absolute right-2 top-2 btn btn-xs btn-square btn-ghost text-slate-300 hover:text-white hover:bg-slate-700/50" title="Copy SQL">
                                                    <x-icon name="o-clipboard" class="w-3.5 h-3.5" />
                                                </button>
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Chart Visualizations -->
                                    @if(isset($msg->metadata['chart_data']) && $msg->metadata['chart_data'])
                                        <div class="mt-4 pt-3 border-t border-base-200">
                                            <div class="flex items-center justify-between mb-3">
                                                <span class="text-xs font-bold text-base-content/75 flex items-center gap-1">
                                                    <x-icon name="o-presentation-chart-bar" class="w-4 h-4 text-primary" />
                                                    CHART REPORT
                                                </span>
                                                <!-- Chart Type Selectors -->
                                                <div class="join bg-base-200/50 p-0.5 rounded-lg">
                                                    <button wire:click="changeChartType({{ $msg->id }}, 'bar')" class="btn btn-xxs join-item {{ ($msg->metadata['chart_type'] ?? 'bar') === 'bar' ? 'btn-primary text-primary-content' : 'btn-ghost text-base-content/70' }}">Bar</button>
                                                    <button wire:click="changeChartType({{ $msg->id }}, 'line')" class="btn btn-xxs join-item {{ ($msg->metadata['chart_type'] ?? 'bar') === 'line' ? 'btn-primary text-primary-content' : 'btn-ghost text-base-content/70' }}">Line</button>
                                                    <button wire:click="changeChartType({{ $msg->id }}, 'pie')" class="btn btn-xxs join-item {{ ($msg->metadata['chart_type'] ?? 'bar') === 'pie' ? 'btn-primary text-primary-content' : 'btn-ghost text-base-content/70' }}">Donut</button>
                                                </div>
                                            </div>
                                            
                                            <!-- SVG Output container -->
                                            <div class="p-3 bg-base-50 rounded-xl border border-base-200 flex items-center justify-center">
                                                {!! $this->renderSvgChart($msg->metadata['chart_data'], $msg->metadata['chart_type'] ?? 'bar') !!}
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Tabular Query Result Data Grid -->
                                    @if(!empty($msg->metadata['rows']))
                                        <div class="mt-4 pt-3 border-t border-base-200" x-data="{ showGrid: true }">
                                            <div class="flex items-center justify-between mb-2">
                                                <button @click="showGrid = !showGrid" class="btn btn-xs btn-ghost gap-1 text-[11px] font-semibold text-primary">
                                                    <x-icon name="o-table-cells" class="w-3.5 h-3.5" />
                                                    <span x-text="showGrid ? 'Hide Data Grid' : 'Show Data Grid'"></span>
                                                    <span class="badge badge-sm badge-ghost text-[10px]">{{ count($msg->metadata['rows']) }} rows</span>
                                                </button>
                                                <button wire:click="exportCsv({{ $msg->id }})" class="btn btn-xs btn-outline gap-1 text-[10px] hover:btn-success">
                                                    <x-icon name="o-arrow-down-tray" class="w-3 h-3" />
                                                    Export CSV
                                                </button>
                                            </div>

                                            <div x-show="showGrid" x-cloak class="overflow-x-auto max-h-[200px] border border-base-200 rounded-lg bg-base-50">
                                                <table class="table table-xs w-full divide-y divide-base-200">
                                                    <thead class="bg-base-200/50 sticky top-0">
                                                        <tr>
                                                            @foreach($msg->metadata['columns'] ?? [] as $col)
                                                                <th class="font-bold text-[10px] uppercase text-base-content/80 font-mono">{{ $col }}</th>
                                                            @endforeach
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-base-100">
                                                        @foreach(array_slice($msg->metadata['rows'], 0, 30) as $row)
                                                            <tr class="hover:bg-base-100/80">
                                                                @foreach($msg->metadata['columns'] ?? [] as $col)
                                                                    <td class="text-[11px] font-mono whitespace-nowrap text-base-content/70">{{ strval($row[$col] ?? '') }}</td>
                                                                @endforeach
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                            @if(count($msg->metadata['rows']) > 30)
                                                <p class="text-[9px] text-base-content/50 mt-1.5 text-right italic">Showing first 30 rows only.</p>
                                            @endif
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @empty
                        <!-- SMART SUGGESTIONS / WELCOME BOARD -->
                        <div class="flex flex-col items-center justify-center py-12 px-4 max-w-xl mx-auto h-full text-center">
                            <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center text-primary mb-6 animate-pulse">
                                <x-icon name="o-sparkles" class="w-8 h-8" />
                            </div>
                            <h2 class="text-xl font-bold text-base-content mb-2">Welcome to your SQL Business Intelligence Workspace</h2>
                            <p class="text-sm text-base-content/60 mb-8">Select any database tables or ask questions to automatically generate SQL code, extract query datasets, and render custom report charts instantly.</p>
                            
                            <!-- Smart suggestion chips -->
                            <div class="w-full space-y-3">
                                <p class="text-xs font-bold text-base-content/40 tracking-wider uppercase mb-2">Suggested Queries for your Schema</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    @foreach($this->getSmartSuggestions() as $sug)
                                        <button wire:click="selectSuggestion('{{ addslashes($sug['text']) }}', '{{ addslashes($sug['question']) }}')" class="flex items-start gap-2.5 p-3 rounded-xl border border-base-200 hover:border-primary/50 hover:bg-primary/5 text-left text-xs font-medium text-base-content/80 hover:text-primary transition-all duration-200">
                                            <x-icon name="{{ $sug['icon'] }}" class="w-4 h-4 mt-0.5 text-primary" />
                                            <span>{{ $sug['text'] }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforelse

                    <!-- Steps Progress Panel -->
                    @if($isProcessing)
                        <div class="flex justify-start">
                            <div class="max-w-2xl bg-base-100 rounded-2xl p-4 border border-base-200 shadow-md space-y-3">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="loading loading-spinner loading-xs text-primary"></span>
                                    <span class="text-xs font-bold text-base-content/85">Processing Query Sequence...</span>
                                </div>
                                <div class="space-y-2 pl-6">
                                    @foreach($steps as $key => $step)
                                        <div class="flex items-center justify-between text-xs gap-6">
                                            <div class="flex items-center gap-2">
                                                @if($step['status'] === 'running')
                                                    <span class="loading loading-ring loading-xs text-info"></span>
                                                    <span class="font-medium text-base-content/90">{{ $step['title'] }}</span>
                                                @elseif($step['status'] === 'success')
                                                    <x-icon name="o-check-circle" class="w-4 h-4 text-success" />
                                                    <span class="text-base-content/60 line-through">{{ $step['title'] }}</span>
                                                @elseif($step['status'] === 'failed')
                                                    <x-icon name="o-x-circle" class="w-4 h-4 text-error" />
                                                    <span class="text-error font-semibold">{{ $step['title'] }} (Failed)</span>
                                                @else
                                                    <div class="w-4 h-4 rounded-full border border-base-300 flex items-center justify-center text-[8px] text-base-content/30">•</div>
                                                    <span class="text-base-content/40">{{ $step['title'] }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Input area -->
                <div class="p-4 border-t border-base-200 bg-base-100">
                    <form wire:submit.prevent="askAi()" class="relative flex items-center gap-2">
                        <input type="text" wire:model="question" placeholder="ডাটাবেসকে প্রশ্ন করুন (যেমন: চলতি মাসে মোট সেলস কত?)..." class="input input-bordered w-full pr-12 focus:outline-none focus:ring-1 focus:ring-primary text-sm bg-base-50 text-base-content" required :disabled="$isProcessing" />
                        <button type="submit" class="absolute right-2 btn btn-xs btn-circle btn-primary" :disabled="$isProcessing" title="Send Request">
                            <x-icon name="o-paper-airplane" class="w-3.5 h-3.5" />
                        </button>
                    </form>
                </div>
            @else
                <!-- No chat session active -->
                <div class="flex-1 flex flex-col items-center justify-center p-8 bg-base-50/20 text-center">
                    <div class="w-16 h-16 rounded-2xl bg-base-100 border border-base-200 flex items-center justify-center text-base-content/30 mb-4 shadow-sm">
                        <x-icon name="o-circle-stack" class="w-8 h-8" />
                    </div>
                    <h3 class="text-base font-bold text-base-content mb-1">No Chat Session Selected</h3>
                    <p class="text-xs text-base-content/50 max-w-sm mb-4">Choose an existing database conversation session from the sidebar or start a new one.</p>
                    <button wire:click="createNewConversation" class="btn btn-sm btn-primary">
                        <x-icon name="o-plus" class="w-4 h-4" />
                        Start SQL Session
                    </button>
                </div>
            @endif
        </div>
    </div>

    <!-- BI SETTINGS DRAWER -->
    <x-drawer wire:model="showSettingsDrawer" title="Session BI Settings" right class="w-96" separator>
        <div class="flex flex-col h-full justify-between p-4 space-y-6">
            <div class="space-y-4">
                <!-- Select LLM Provider -->
                <div class="form-control w-full">
                    <label class="label font-semibold text-xs text-base-content/70">AI Provider</label>
                    <select wire:model.live="aiProvider" class="select select-bordered select-sm w-full bg-base-50 text-base-content">
                        @foreach($this->availableProviders as $provKey => $provName)
                            <option value="{{ $provKey }}">{{ $provName }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Select LLM Model -->
                <div class="form-control w-full">
                    <label class="label font-semibold text-xs text-base-content/70">Language Model</label>
                    <select wire:model="model" class="select select-bordered select-sm w-full bg-base-50 text-base-content">
                        @foreach($this->availableModels as $modelKey => $modelName)
                            <option value="{{ $modelKey }}">{{ $modelName }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Response Language -->
                <div class="form-control w-full">
                    <label class="label font-semibold text-xs text-base-content/70">Response Language</label>
                    <select wire:model="responseLanguage" class="select select-bordered select-sm w-full bg-base-50 text-base-content">
                        <option value="bn">Bengali (বাংলা)</option>
                        <option value="en">English (English)</option>
                    </select>
                </div>

                <!-- Custom System Instructions -->
                <div class="form-control w-full">
                    <label class="label font-semibold text-xs text-base-content/70">System SQL Guardrails Prompt</label>
                    <textarea wire:model="systemPrompt" class="textarea textarea-bordered textarea-sm w-full h-24 bg-base-50 text-xs text-base-content" placeholder="Tune SQL generation constraints..."></textarea>
                </div>
                
                <!-- Active Tables Context Selection -->
                <div class="form-control w-full">
                    <label class="label font-semibold text-xs text-base-content/70">Active Database Schema Context</label>
                    <div class="border border-base-200 rounded-lg p-3 max-h-[220px] overflow-y-auto bg-base-50 space-y-2">
                        @foreach($this->getDatabaseTablesMetadata() as $tblName => $tblMeta)
                            <label class="flex items-center gap-2 cursor-pointer hover:bg-base-100 p-1 rounded">
                                <input type="checkbox" wire:model="activeTables" value="{{ $tblName }}" class="checkbox checkbox-xs checkbox-primary" />
                                <span class="text-xs font-mono truncate text-base-content/85">{{ $tblName }}</span>
                                <span class="text-[9px] text-base-content/40">({{ $tblMeta['rows'] }} r)</span>
                            </label>
                        @endforeach
                    </div>
                    <span class="text-[10px] text-base-content/40 mt-1 block">Uncheck tables to exclude them from the AI's search context window.</span>
                </div>
            </div>

            <div class="flex items-center gap-2 pt-4 border-t border-base-200">
                <button wire:click="saveSettings" class="btn btn-sm btn-primary flex-1">Save BI Settings</button>
                <button @click="$wire.showSettingsDrawer = false" class="btn btn-sm btn-ghost">Cancel</button>
            </div>
        </div>
    </x-drawer>

    <!-- SCHEMA DETAILS DICTIONARY DRAWER -->
    <x-drawer wire:model="showSchemaDrawer" title="Database Tables Dictionary" right class="w-[500px]" separator>
        <div class="p-4 space-y-6 overflow-y-auto h-full max-h-[85vh]">
            <p class="text-xs text-base-content/50">Below is a detailed breakdown of all the tables, columns, data types, and primary keys available in your database schema. Use these fields to formulate precise questions.</p>
            
            <div class="space-y-4">
                @foreach($this->getDatabaseTablesMetadata() as $tblName => $tblMeta)
                    <div class="collapse collapse-arrow bg-base-50 border border-base-200/50 rounded-xl" x-data="{ openTableDict: false }">
                        <input type="checkbox" @click="openTableDict = !openTableDict" /> 
                        <div class="collapse-title text-xs font-bold font-mono text-base-content flex items-center justify-between pr-8">
                            <span>{{ $tblName }}</span>
                            <span class="badge badge-sm badge-ghost">{{ $tblMeta['rows'] }} rows</span>
                        </div>
                        <div class="collapse-content overflow-x-auto">
                            <table class="table table-xs w-full divide-y divide-base-200">
                                <thead>
                                    <tr>
                                        <th class="text-[9px] font-bold">Field</th>
                                        <th class="text-[9px] font-bold">Type</th>
                                        <th class="text-[9px] font-bold">Key</th>
                                        <th class="text-[9px] font-bold">Null</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tblMeta['columns'] as $col)
                                        <tr>
                                            <td class="font-mono text-[10px] font-bold text-base-content/85">{{ $col['field'] }}</td>
                                            <td class="font-mono text-[9px] text-base-content/60">{{ $col['type'] }}</td>
                                            <td>
                                                @if($col['key'] === 'PRI')
                                                    <span class="badge badge-primary badge-xs text-[8px] font-bold font-mono">PRI</span>
                                                @elseif($col['key'])
                                                    <span class="badge badge-ghost badge-xs text-[8px] font-mono">{{ $col['key'] }}</span>
                                                @endif
                                            </td>
                                            <td class="font-mono text-[9px] text-base-content/40">{{ $col['nullable'] ? 'Yes' : 'No' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </x-drawer>
</div>

<script>
function askAiChat() {
    return {
        init() {
            this.scrollToBottom();
            
            // Re-scroll on updates
            Livewire.on('scroll-to-bottom', () => {
                setTimeout(() => this.scrollToBottom(), 100);
            });
            
            // Scroll when window loaded
            this.$nextTick(() => {
                this.scrollToBottom();
            });
        },
        scrollToBottom() {
            const container = this.$refs.messagesContainer;
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        }
    }
}
</script>