<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        {{--
            mt-8 keeps the button floating clear of the «Адрес офиса»
            section's bottom border — `pt-6 border-t` made it read as
            «attached to the card above» visually. Cleaner as a free-
            standing toolbar.
        --}}
        <div class="flex justify-end gap-3 mt-8">
            <x-filament::button type="submit" size="lg">
                Сохранить
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
