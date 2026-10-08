<x-app-layout title="Chat interno">
    <x-slot name="header">
        <x-page-header title="Chat interno" subtitle="Mensajería entre los abogados del bufete" />
    </x-slot>

    <div class="card flex h-[calc(100vh-14rem)] min-h-[480px] overflow-hidden"
         x-data="chatApp(@js($selected ? ['id' => $selected->id, 'name' => $selected->name, 'role' => $selected->role->label()] : null), @js($unread))">
        {{-- Contactos --}}
        <aside class="w-full shrink-0 border-r border-gray-200 dark:border-gray-700 sm:w-72" :class="current ? 'hidden sm:block' : 'block'">
            <div class="border-b border-gray-100 p-3 dark:border-gray-700">
                <input type="search" x-model="filter" placeholder="Buscar compañero…" class="form-control py-1.5">
            </div>
            <ul class="h-full overflow-y-auto pb-16">
                @foreach ($contacts as $c)
                    <li x-show="!filter || @js(mb_strtolower($c->name)).includes(filter.toLowerCase())">
                        <button type="button" @click="open({ id: {{ $c->id }}, name: @js($c->name), role: @js($c->role->label()) })"
                                class="flex w-full items-center gap-3 px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700/40"
                                :class="current?.id === {{ $c->id }} && 'bg-primary-50 dark:bg-primary-500/10'">
                            <x-avatar :name="$c->name" />
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold">{{ $c->name }}</span>
                                <span class="block truncate text-xs text-gray-500">{{ isset($last[$c->id]) ? \Illuminate\Support\Str::limit($last[$c->id]->body, 34) : $c->role->label() }}</span>
                            </span>
                            <span x-show="unread[{{ $c->id }}] > 0" x-text="unread[{{ $c->id }}]" class="rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] font-bold text-white"></span>
                        </button>
                    </li>
                @endforeach
            </ul>
        </aside>

        {{-- Conversación --}}
        <section class="flex min-w-0 flex-1 flex-col" :class="current ? 'flex' : 'hidden sm:flex'">
            <template x-if="!current">
                <div class="flex flex-1 items-center justify-center"><x-empty icon="chat" title="Seleccione un compañero" message="Coordine audiencias, documentos y tareas con su equipo." /></div>
            </template>
            <template x-if="current">
                <div class="flex h-full flex-col">
                    <div class="flex items-center gap-3 border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                        <button class="btn btn-ghost btn-sm sm:hidden" @click="current = null"><x-icon name="arrow-left" class="h-5 w-5" /></button>
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-600 text-sm font-semibold text-white" x-text="initials(current.name)"></span>
                        <div><p class="text-sm font-semibold" x-text="current.name"></p><p class="text-xs text-gray-500" x-text="current.role"></p></div>
                    </div>
                    <div x-ref="scroller" class="flex-1 space-y-2 overflow-y-auto bg-gray-50 p-4 dark:bg-gray-900/40">
                        <template x-for="m in messages" :key="m.id">
                            <div class="flex" :class="m.mine ? 'justify-end' : 'justify-start'">
                                <div class="max-w-[75%] rounded-2xl px-3.5 py-2 text-sm shadow-sm"
                                     :class="m.mine ? 'rounded-br-sm bg-primary-600 text-white' : 'rounded-bl-sm bg-white text-gray-800 dark:bg-gray-800 dark:text-gray-100'">
                                    <p class="whitespace-pre-line break-words" x-text="m.body"></p>
                                    <p class="mt-0.5 text-right text-[10px] opacity-70"><span x-text="m.time"></span><span x-show="m.mine" x-text="m.read ? ' ✓✓' : ' ✓'"></span></p>
                                </div>
                            </div>
                        </template>
                        <p x-show="messages.length === 0 && !loading" class="py-10 text-center text-sm text-gray-400">Aún no hay mensajes. ¡Escriba el primero!</p>
                    </div>
                    <form @submit.prevent="send" class="flex items-end gap-2 border-t border-gray-200 p-3 dark:border-gray-700">
                        <textarea x-model="draft" @keydown.enter.prevent="if (!$event.shiftKey) send(); else draft += '\n'" rows="1" placeholder="Escriba un mensaje… (Enter para enviar)" class="form-control max-h-32 resize-none"></textarea>
                        <button class="btn btn-primary" :disabled="!draft.trim() || sending"><x-icon name="send" class="h-4 w-4" /></button>
                    </form>
                </div>
            </template>
        </section>
    </div>

    @push('scripts')
        <script>
            function chatApp(initial, unread) {
                return {
                    current: null, messages: [], draft: '', filter: '', loading: false, sending: false, timer: null,
                    unread: unread || {},
                    init() {
                        if (initial) this.open(initial);
                        this.timer = setInterval(() => this.poll(), 4000);
                    },
                    initials(name) { return name.split(/\s+/).slice(0, 2).map(p => p[0]).join('').toUpperCase(); },
                    async open(contact) {
                        this.current = contact; this.messages = []; this.loading = true;
                        history.replaceState(null, '', '?user=' + contact.id);
                        const { data } = await axios.get(`/chat/${contact.id}/messages`);
                        this.messages = data.messages; this.loading = false;
                        this.unread[contact.id] = 0;
                        this.scroll();
                        Alpine.store('counters').refresh();
                    },
                    async poll() {
                        if (document.hidden) return;
                        try {
                            if (this.current) {
                                const after = this.messages.length ? this.messages[this.messages.length - 1].id : 0;
                                const { data } = await axios.get(`/chat/${this.current.id}/messages`, { params: { after } });
                                if (data.messages.length) { this.messages.push(...data.messages); this.scroll(); }
                            }
                            const { data } = await axios.get('/chat/unread');
                            this.unread = data.by_user || {};
                            if (this.current) this.unread[this.current.id] = 0;
                        } catch (e) {}
                    },
                    async send() {
                        const body = this.draft.trim();
                        if (!body || !this.current) return;
                        this.sending = true;
                        try {
                            const { data } = await axios.post(`/chat/${this.current.id}/messages`, { body });
                            this.messages.push(data.message); this.draft = ''; this.scroll();
                        } catch (e) { alert('No se pudo enviar el mensaje.'); }
                        this.sending = false;
                    },
                    scroll() { this.$nextTick(() => { const el = this.$refs.scroller; if (el) el.scrollTop = el.scrollHeight; }); },
                };
            }
        </script>
    @endpush
</x-app-layout>
