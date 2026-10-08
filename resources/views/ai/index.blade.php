<x-app-layout title="Asistente IA">
    <x-slot name="header">
        <x-page-header title="Asistente legal con IA" subtitle="Pregunte en lenguaje natural sobre sus casos, audiencias, pagos… o pida ayuda para redactar escritos.">
            <span class="badge {{ \App\Support\Badge::classes($provider === 'local' ? 'amber' : 'emerald') }}"><x-icon name="sparkles" class="h-3.5 w-3.5" /> {{ $providerLabel }}</span>
            <button type="button" class="btn btn-secondary" x-data @click="$dispatch('open-modal', 'ai-instructions')"><x-icon name="cog" class="h-4 w-4" /> Instrucciones personalizadas</button>
        </x-page-header>
    </x-slot>

    <div class="flex h-[calc(100vh-14rem)] min-h-[520px] gap-6">
        {{-- Historial --}}
        <aside class="card hidden w-64 shrink-0 flex-col lg:flex">
            <form method="POST" action="{{ route('ai.store') }}" class="border-b border-gray-100 p-3 dark:border-gray-700">
                @csrf
                <button class="btn btn-primary w-full"><x-icon name="plus" class="h-4 w-4" /> Nueva conversación</button>
            </form>
            <ul class="flex-1 overflow-y-auto p-2">
                @forelse ($conversations as $c)
                    <li class="group flex items-center rounded-lg {{ $conversation?->id === $c->id ? 'bg-primary-50 dark:bg-primary-500/10' : 'hover:bg-gray-50 dark:hover:bg-gray-700/40' }}">
                        <a href="{{ route('ai.show', $c) }}" class="min-w-0 flex-1 px-3 py-2">
                            <span class="block truncate text-sm font-medium">{{ $c->title }}</span>
                            <span class="block text-[11px] text-gray-400">{{ $c->updated_at->diffForHumans() }}</span>
                        </a>
                        <form method="POST" action="{{ route('ai.destroy', $c) }}" data-confirm="¿Eliminar esta conversación?" class="hidden pr-1 group-hover:block">
                            @csrf @method('DELETE')
                            <button class="btn btn-ghost btn-sm p-1 text-gray-400 hover:text-red-600"><x-icon name="trash" class="h-4 w-4" /></button>
                        </form>
                    </li>
                @empty
                    <li class="p-3 text-center text-xs text-gray-400">Sin conversaciones.</li>
                @endforelse
            </ul>
        </aside>

        {{-- Conversación --}}
        <section class="card flex min-w-0 flex-1 flex-col"
                 x-data="aiChat({{ $conversation?->id ?? 'null' }}, @js($conversation ? $conversation->messages->map->toChatArray()->values() : []))">
            <div x-ref="scroller" class="flex-1 space-y-5 overflow-y-auto p-5">
                <template x-if="messages.length === 0">
                    <div class="mx-auto max-w-2xl py-8 text-center">
                        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-primary-100 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400"><x-icon name="sparkles" class="h-8 w-8" /></div>
                        <h2 class="text-lg font-semibold">¿En qué le puedo ayudar hoy?</h2>
                        <p class="mt-1 text-sm text-gray-500">El asistente conoce sus casos, audiencias, citas, pagos y gastos (según sus permisos).</p>
                        <div class="mt-6 grid gap-2 sm:grid-cols-2">
                            @foreach ([
                                '¿Qué audiencias tengo esta semana?',
                                '¿Qué clientes tienen saldo pendiente y cuánto?',
                                'Resume el estado de mis casos activos',
                                'Redacta un derecho de petición para solicitar copias de un expediente',
                                '¿Qué gastos están pendientes de aprobación?',
                                'Explica los términos para interponer un recurso de apelación',
                            ] as $s)
                                <button type="button" @click="draft = @js($s); send()" class="rounded-lg border border-gray-200 px-3 py-2 text-left text-sm hover:border-primary-400 hover:bg-primary-50 dark:border-gray-700 dark:hover:bg-primary-500/10">{{ $s }}</button>
                            @endforeach
                        </div>
                    </div>
                </template>
                <template x-for="m in messages" :key="m.id">
                    <div class="flex gap-3" :class="m.role === 'user' ? 'flex-row-reverse' : ''">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                             :class="m.role === 'user' ? 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200' : 'bg-primary-600 text-white'"
                             x-text="m.role === 'user' ? '{{ auth()->user()->initials }}' : 'IA'"></div>
                        <div class="max-w-[85%] rounded-2xl px-4 py-2.5 text-sm"
                             :class="m.role === 'user' ? 'rounded-tr-sm bg-primary-600 text-white' : (m.error ? 'rounded-tl-sm bg-red-50 text-red-800 dark:bg-red-500/10 dark:text-red-200' : 'rounded-tl-sm bg-gray-100 text-gray-800 dark:bg-gray-700/60 dark:text-gray-100')">
                            <div class="prose-chat" x-html="m.html"></div>
                            <p class="mt-1 text-[10px] opacity-60" x-show="m.time"><span x-text="m.time"></span><span x-show="m.provider" x-text="' · ' + providerName(m.provider)"></span></p>
                        </div>
                    </div>
                </template>
                <div x-show="thinking" x-cloak class="flex gap-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-600 text-xs font-bold text-white">IA</div>
                    <div class="flex items-center gap-1 rounded-2xl bg-gray-100 px-4 py-3 dark:bg-gray-700/60">
                        <span class="h-2 w-2 animate-bounce rounded-full bg-gray-400"></span>
                        <span class="h-2 w-2 animate-bounce rounded-full bg-gray-400 [animation-delay:150ms]"></span>
                        <span class="h-2 w-2 animate-bounce rounded-full bg-gray-400 [animation-delay:300ms]"></span>
                    </div>
                </div>
            </div>
            <form @submit.prevent="send" class="border-t border-gray-200 p-3 dark:border-gray-700">
                <div class="flex items-end gap-2">
                    <textarea x-model="draft" x-ref="input" @keydown.enter.prevent="if (!$event.shiftKey) send(); else draft += '\n'" rows="2"
                              placeholder="Escriba su consulta… (Enter para enviar, Shift+Enter para nueva línea)" class="form-control resize-none"></textarea>
                    <button class="btn btn-primary h-10" :disabled="!draft.trim() || thinking"><x-icon name="send" class="h-4 w-4" /></button>
                </div>
                <p class="mt-1.5 text-[11px] text-gray-400">Las respuestas de la IA son orientativas y no sustituyen el criterio profesional. Verifique la normativa vigente.</p>
            </form>
        </section>
    </div>

    <x-dialog name="ai-instructions" title="Instrucciones personalizadas" max-width="2xl">
        <form method="POST" action="{{ route('ai.instructions') }}" class="space-y-4">
            @csrf @method('PUT')
            <p class="text-sm text-gray-500">Indique cómo quiere que responda el asistente: su especialidad, el tono, el formato o la jurisdicción. Se aplican a todas sus conversaciones.</p>
            <textarea name="ai_instructions" rows="7" class="form-control" placeholder="Ej.: Soy abogada litigante en derecho de familia en Colombia. Responde de forma concisa, cita los artículos del Código General del Proceso cuando aplique y usa viñetas.">{{ auth()->user()->ai_instructions }}</textarea>
            <div class="flex justify-end gap-2">
                <button type="button" class="btn btn-secondary" @click="$dispatch('close')">Cancelar</button>
                <button class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </x-dialog>

    @push('scripts')
        <script>
            function aiChat(conversationId, initial) {
                return {
                    conversationId, messages: initial, draft: '', thinking: false,
                    init() { this.scroll(); },
                    providerName(p) { return { gemini: 'Gemini', openai: 'ChatGPT', local: 'Modo sin conexión' }[p] || p; },
                    escape(t) { const d = document.createElement('div'); d.textContent = t; return d.innerHTML.replace(/\n/g, '<br>'); },
                    async send() {
                        const text = this.draft.trim();
                        if (!text || this.thinking) return;
                        const tempId = 'tmp' + Date.now();
                        this.messages.push({ id: tempId, role: 'user', html: this.escape(text) });
                        this.draft = ''; this.thinking = true; this.scroll();
                        try {
                            const { data } = await axios.post('{{ route('ai.message') }}', { message: text, conversation_id: this.conversationId });
                            this.messages = this.messages.filter(m => m.id !== tempId);
                            this.messages.push(data.user_message, data.reply);
                            if (!this.conversationId) {
                                this.conversationId = data.conversation.id;
                                history.replaceState(null, '', data.conversation.url);
                            }
                        } catch (e) {
                            const msg = e.response?.data?.error || e.response?.data?.message || 'Error al contactar al asistente.';
                            this.messages.push({ id: 'err' + Date.now(), role: 'assistant', error: true, html: this.escape('⚠ ' + msg) });
                        }
                        this.thinking = false; this.scroll();
                        this.$nextTick(() => this.$refs.input?.focus());
                    },
                    scroll() { this.$nextTick(() => { const el = this.$refs.scroller; if (el) el.scrollTop = el.scrollHeight; }); },
                };
            }
        </script>
    @endpush
</x-app-layout>
