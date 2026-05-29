<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        {{--
            mt-12 keeps a clear gap between the form's last input card
            and the Save button — mt-8 was too tight against the card,
            user reported it as «прилипшая». Free-standing toolbar with
            real breathing room.
        --}}
        <div class="flex justify-end gap-3 mt-12">
            <x-filament::button type="submit" size="lg">
                Сохранить
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
