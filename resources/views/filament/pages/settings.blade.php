<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex justify-end gap-3 pt-6 border-t border-gray-200 dark:border-gray-800">
            <x-filament::button type="submit" size="lg">
                Сохранить
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
