<x-card title="Datos del gasto" icon="receipt">
    <div class="grid grid-cols-1 gap-5 md:grid-cols-6">
        <x-field label="Caso" name="legal_case_id" required class="md:col-span-6">
            <select name="legal_case_id" required class="form-control">
                <option value="">Seleccione…</option>
                @foreach ($cases as $c)<option value="{{ $c->id }}" @selected(old('legal_case_id', $expense->legal_case_id) == $c->id)>{{ $c->case_number }} — {{ \Illuminate\Support\Str::limit($c->title, 60) }} ({{ $c->client?->name }})</option>@endforeach
            </select>
        </x-field>
        <x-field label="Categoría" name="category" required class="md:col-span-3">
            <select name="category" required class="form-control">
                @foreach (config('bufete.expense_categories') as $k => $l)<option value="{{ $k }}" @selected(old('category', $expense->category) === $k)>{{ $l }}</option>@endforeach
            </select>
        </x-field>
        <x-field label="Fecha del gasto" name="expense_date" required class="md:col-span-3">
            <input type="date" name="expense_date" value="{{ old('expense_date', $expense->expense_date?->format('Y-m-d')) }}" max="{{ today()->format('Y-m-d') }}" required class="form-control">
        </x-field>
        <x-field label="Descripción" name="description" required class="md:col-span-4">
            <input name="description" value="{{ old('description', $expense->description) }}" required class="form-control" placeholder="Ej. Copias autenticadas del expediente">
        </x-field>
        <x-field label="Valor" name="amount" required class="md:col-span-2">
            <div class="relative">
                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400">{{ setting('currency_symbol', '$') }}</span>
                <input type="number" min="0.01" step="0.01" name="amount" value="{{ old('amount', $expense->amount ? (float) $expense->amount : '') }}" required class="form-control pl-8">
            </div>
        </x-field>
        <x-field label="Soporte (factura / recibo)" name="receipt" class="md:col-span-4" :hint="$expense->receipt_path ? 'Actual: '.$expense->receipt_original_name.' — suba otro archivo para reemplazarlo.' : 'PDF o imagen, máximo 10 MB.'">
            <input type="file" name="receipt" accept=".pdf,.jpg,.jpeg,.png,.webp" class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-primary-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-primary-700 dark:text-gray-300 dark:file:bg-primary-500/10 dark:file:text-primary-300">
        </x-field>
        <div class="flex items-center md:col-span-2">
            <input type="hidden" name="billable" value="0">
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="billable" value="1" class="form-check" @checked(old('billable', $expense->billable ?? true))> Facturable al cliente</label>
        </div>
    </div>
</x-card>
