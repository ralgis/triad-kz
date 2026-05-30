<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Step 1: Upload --}}
        @if($preview === null)
            <div class="fi-section fi-section-has-content overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="fi-section-header flex flex-col gap-3 px-6 py-4">
                    <h2 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                        Загрузка XLSX
                    </h2>
                    <p class="fi-section-header-description text-sm text-gray-500 dark:text-gray-400">
                        <strong>Формат:</strong> в первой колонке — SKU (например <code>КС10.9</code>),
                        во второй — цена (числом, без валюты). Заголовок первой строки можно оставить,
                        мы определим автоматически. Максимум 5 МБ, расширение <code>.xlsx</code> или <code>.xls</code>.
                    </p>
                </div>
                <div class="fi-section-content px-6 py-4 border-t border-gray-200 dark:border-white/10">
                    <form wire:submit.prevent="handlePreview" class="space-y-4">
                        <input type="file" wire:model="upload" accept=".xlsx,.xls"
                               class="block w-full text-sm text-gray-900 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-amber-500 file:text-white hover:file:bg-amber-600 dark:text-gray-100">
                        @error('upload')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                        <div wire:loading wire:target="upload" class="text-sm text-gray-500">Загружаю файл…</div>
                        <button type="submit"
                                class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-primary fi-color-primary fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-amber-500 hover:bg-amber-600 text-white">
                            <span wire:loading.remove wire:target="handlePreview">Обработать файл</span>
                            <span wire:loading wire:target="handlePreview">Обрабатываю…</span>
                        </button>
                    </form>
                </div>
            </div>
        @endif

        {{-- Step 2: Preview --}}
        @if($preview !== null)
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 px-4 py-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Будет обновлено</p>
                    <p class="text-2xl font-bold text-green-600">{{ count($preview['matched']) }}</p>
                </div>
                <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 px-4 py-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Без изменений</p>
                    <p class="text-2xl font-bold text-gray-500">{{ count($preview['noop']) }}</p>
                </div>
                <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 px-4 py-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">SKU не найдено</p>
                    <p class="text-2xl font-bold text-red-600">{{ count($preview['not_found']) }}</p>
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <button wire:click="handleApply"
                        class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition rounded-lg gap-1.5 px-4 py-2 text-sm inline-grid shadow-sm bg-amber-500 hover:bg-amber-600 text-white">
                    Применить изменения
                </button>
                <button wire:click="handleCancel"
                        class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition rounded-lg gap-1.5 px-4 py-2 text-sm inline-grid ring-1 ring-gray-300 hover:bg-gray-50 text-gray-700 dark:ring-white/10 dark:text-gray-200 dark:hover:bg-white/5">
                    Отмена
                </button>
            </div>

            @if(count($preview['matched']) > 0)
                <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-white/10">
                        <h3 class="font-semibold">Будут обновлены</h3>
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-white/5">
                            <tr>
                                <th class="px-4 py-2 text-left">SKU</th>
                                <th class="px-4 py-2 text-left">Название</th>
                                <th class="px-4 py-2 text-right">Было</th>
                                <th class="px-4 py-2 text-right">Станет</th>
                                <th class="px-4 py-2 text-right">Δ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @foreach($preview['matched'] as $row)
                                @php
                                    $delta = $row['old_price'] !== null ? $row['new_price'] - $row['old_price'] : null;
                                @endphp
                                <tr>
                                    <td class="px-4 py-2 font-mono">{{ $row['sku'] }}</td>
                                    <td class="px-4 py-2">{{ $row['name'] }}</td>
                                    <td class="px-4 py-2 text-right font-mono text-gray-500">
                                        {{ $row['old_price'] !== null ? number_format($row['old_price'], 2, '.', ' ') : '—' }}
                                    </td>
                                    <td class="px-4 py-2 text-right font-mono font-semibold">
                                        {{ number_format($row['new_price'], 2, '.', ' ') }}
                                    </td>
                                    <td class="px-4 py-2 text-right font-mono {{ $delta > 0 ? 'text-red-600' : ($delta < 0 ? 'text-green-600' : 'text-gray-500') }}">
                                        @if($delta !== null)
                                            {{ $delta > 0 ? '+' : '' }}{{ number_format($delta, 2, '.', ' ') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if(count($preview['not_found']) > 0)
                <div class="rounded-xl bg-red-50 ring-1 ring-red-200 dark:bg-red-900/10 dark:ring-red-900/30 px-6 py-4">
                    <h3 class="font-semibold text-red-700 dark:text-red-300 mb-2">SKU не найдены в каталоге</h3>
                    <p class="text-sm text-red-700/80 dark:text-red-300/80 mb-3">
                        Эти строки из файла будут пропущены. Проверь написание артикулов или добавь товары в каталог.
                    </p>
                    <ul class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-1 text-sm font-mono text-red-800 dark:text-red-200">
                        @foreach($preview['not_found'] as $row)
                            <li>{{ $row['sku'] }} <span class="text-red-500/70">(строка {{ $row['row'] }})</span></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endif

        {{-- Recent imports history --}}
        @php($recent = $this->recentImports)
        @if($recent->isNotEmpty())
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-white/10">
                    <h3 class="font-semibold">Последние импорты</h3>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-2 text-left">Дата</th>
                            <th class="px-4 py-2 text-left">Файл</th>
                            <th class="px-4 py-2 text-right">Обновлено</th>
                            <th class="px-4 py-2 text-right">Пропущено</th>
                            <th class="px-4 py-2 text-left">Админ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach($recent as $imp)
                            <tr>
                                <td class="px-4 py-2 font-mono text-xs whitespace-nowrap">
                                    {{ $imp->created_at?->translatedFormat('j M Y · H:i') }}
                                </td>
                                <td class="px-4 py-2">{{ $imp->file_name }}</td>
                                <td class="px-4 py-2 text-right font-mono text-green-600">{{ $imp->rows_updated }}</td>
                                <td class="px-4 py-2 text-right font-mono text-gray-500">{{ $imp->rows_skipped }}</td>
                                <td class="px-4 py-2 text-xs">{{ $imp->importedBy?->name ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

    </div>
</x-filament-panels::page>
